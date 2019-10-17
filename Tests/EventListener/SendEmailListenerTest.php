<?php

namespace Schobner\SwiftMailerDBLogBundle\Tests\EventListener;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Faker\Factory;
use Schobner\SwiftMailerDBLogBundle\EventListener\SendEmailListener;
use Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsInterfaceException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Swift_Events_SendEvent;
use Swift_Events_TransportExceptionEvent;
use Swift_Mime_SimpleMessage;
use Swift_TransportException;

class SendEmailListenerTest extends KernelTestCase
{

    /** @var array */
    private $dummyEmailLog;

    /** @var \Schobner\SwiftMailerDBLogBundle\EventListener\SendEmailListener */
    private $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Swift_Events_SendEvent */
    private $sendEvent;

    /**
     * SendEmailListenerTest constructor.
     *
     * @param null $name
     * @param array $data
     * @param string $dataName
     *
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsInterfaceException
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // Dependency
        $faker = Factory::create();

        // Dummy email data
        $this->dummyEmailLog = (new ExampleEmailLogEntity())
            ->setMessageId($faker->regexify('[a-z0-9]{32}').'@swift.generated')
            ->setEmailFrom([$faker->email => $faker->company])
            ->setEmailReplyTo($faker->email)
            ->setEmailTo([$faker->email => $faker->firstName.' '.$faker->lastName])
            ->setEmailCc([$faker->email => $faker->firstName.' '.$faker->lastName])
            ->setEmailBcc([$faker->email => $faker->firstName.' '.$faker->lastName])
            ->setSubject('Test subject text')
            ->setEml('Binary email content');

        // Mock email log repo
        $or = $this->createMock(ObjectRepository::class);
        $or->method('findOneBy')->willReturn($this->dummyEmailLog);

        // Mock entity manager
        $emMock = $this->createMock(EntityManagerInterface::class);
        $emMock->method('getRepository')->willReturn($or);
        $emMock->method('persist')->willReturn(null);
        $emMock->method('flush')->willReturn(null);

        // Mock swift message
        $swiftMessageMock = $this->createMock(Swift_Mime_SimpleMessage::class);
        $swiftMessageMock->method('getId')->willReturn($this->dummyEmailLog->getMessageId());
        $swiftMessageMock->method('getFrom')->willReturn($this->dummyEmailLog->getEmailFrom());
        $swiftMessageMock->method('getReplyTo')->willReturn($this->dummyEmailLog->getEmailReplyTo());
        $swiftMessageMock->method('getTo')->willReturn($this->dummyEmailLog->getEmailTo());
        $swiftMessageMock->method('getCc')->willReturn($this->dummyEmailLog->getEmailCc());
        $swiftMessageMock->method('getBcc')->willReturn($this->dummyEmailLog->getEmailBcc());
        $swiftMessageMock->method('getSubject')->willReturn($this->dummyEmailLog->getSubject());
        $swiftMessageMock->method('toString')->willReturn($this->dummyEmailLog->getEml());

        // Mock swift send event
        $this->sendEvent = $this->createMock(Swift_Events_SendEvent::class);
        $this->sendEvent->method('getMessage')->willReturn($swiftMessageMock);

        // The listener
        $this->listener = new SendEmailListener($emMock, ExampleEmailLogEntity::class);
    }

    /**
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsInterfaceException
     */
    public function testException(): void
    {
        $this->expectException(ClassNotImplementsInterfaceException::class);

        $emMock = $this->createMock(EntityManagerInterface::class);
        new SendEmailListener($emMock, Exception::class);
    }

    /**
     * @dataProvider missingParameterProvider
     *
     * @param string $method
     * @param string $parameter_class
     *
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsInterfaceException
     */
    public function testMissingConfigParameter(string $method, string $parameter_class): void
    {
        // Simulate missing parameter
        $emMock = $this->createMock(EntityManagerInterface::class);
        $this->listener = new SendEmailListener($emMock, '');

        // Test send email
        $eventMock = $this->createMock($parameter_class);
        $this->listener->$method($eventMock);

        self::assertTrue(true); // In error case, this line will not be processed
    }

    public function missingParameterProvider(): array
    {
        return [
            'beforeSendPerformed' => ['beforeSendPerformed', Swift_Events_SendEvent::class],
            'sendPerformed' => ['sendPerformed', Swift_Events_SendEvent::class],
            'exceptionThrown' => ['exceptionThrown', Swift_Events_TransportExceptionEvent::class],
        ];
    }

    public function testBeforeSendPerformed(): void
    {
        // Simulate new email send
        $this->sendEvent->method('getResult')->willReturn(Swift_Events_SendEvent::RESULT_PENDING);
        $this->listener->beforeSendPerformed($this->sendEvent);

        // Check if all data saved
        $newEmailLog = $this->listener->getEmailLog();
        self::assertEquals($this->dummyEmailLog->getMessageId(), $newEmailLog->getMessageId());
        self::assertEquals($this->dummyEmailLog->getEmailFrom(), $newEmailLog->getEmailFrom());
        self::assertEquals($this->dummyEmailLog->getEmailReplyTo(), $newEmailLog->getEmailReplyTo());
        self::assertEquals($this->dummyEmailLog->getEmailTo(), $newEmailLog->getEmailTo());
        self::assertEquals($this->dummyEmailLog->getEmailCc(), $newEmailLog->getEmailCc());
        self::assertEquals($this->dummyEmailLog->getEmailBcc(), $newEmailLog->getEmailBcc());
        self::assertEquals($this->dummyEmailLog->getSubject(), $newEmailLog->getSubject());
        self::assertEquals($this->dummyEmailLog->getEml(), $newEmailLog->getEml());
        self::assertEquals(Swift_Events_SendEvent::RESULT_PENDING, $newEmailLog->getResultStatus());
    }

    public function testSendPerformed(): void
    {
        // Simulate email update
        $this->sendEvent->method('getResult')->willReturn(Swift_Events_SendEvent::RESULT_SUCCESS);
        $this->listener->sendPerformed($this->sendEvent);

        // Check if data updated
        $newEmailLog = $this->listener->getEmailLog();
        self::assertEquals(Swift_Events_SendEvent::RESULT_SUCCESS, $newEmailLog->getResultStatus());
    }

    /**
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\NoEmailLogInCacheException
     */
    public function testExceptionThrown(): void
    {
        // Simulate new email send
        $this->sendEvent->method('getResult')->willReturn(Swift_Events_SendEvent::RESULT_PENDING);
        $this->listener->beforeSendPerformed($this->sendEvent);

        // Mock email send exception
        $exception_msg = 'Connection could not be established with host localhost [Connection refused #61]';
        $exception = new Swift_TransportException($exception_msg);
        $exceptionEvent = $this->createMock(Swift_Events_TransportExceptionEvent::class);
        $exceptionEvent->method('getException')->willReturn($exception);

        // Simulate email send error
        $this->listener->exceptionThrown($exceptionEvent);

        // Check if data updated
        $newEmailLog = $this->listener->getEmailLog();
        self::assertEquals($exception_msg, $newEmailLog->getSendExceptionMessage());
    }
}
