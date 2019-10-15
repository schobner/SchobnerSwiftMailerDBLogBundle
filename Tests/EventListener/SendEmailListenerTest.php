<?php

namespace Schobner\SwiftMailerDBLogBundle\Tests\EventListener;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Faker\Factory;
use Schobner\SwiftMailerDBLogBundle\EventListener\SendEmailListener;
use Schobner\SwiftMailerDBLogBundle\Exception\ClassNotExistsException;
use Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsInterfaceException;
use Schobner\SwiftMailerDBLogBundle\Exception\ConfigParameterEmptyException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Swift_Events_SendEvent;
use Swift_Mime_SimpleMessage;

class SendEmailListenerTest extends KernelTestCase
{

    /** @var array */
    private $dummyEmailLog;

    /** @var \Doctrine\ORM\EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $emMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Swift_Events_SendEvent */
    private $sendEvent;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // Dependency
        $faker = Factory::create();

        // Dummy email data
        $this->dummyEmailLog = (new ExampleEmailLogEntity())
            ->setMessageId($faker->regexify('[a-z0-9]{32}').'@swift.generated')
            ->setEmailFrom([$faker->email => $faker->company])
            ->setEmailTo([$faker->email => $faker->firstName.' '.$faker->lastName])
            ->setSubject('Test subject text')
            ->setEml('Binary email content');

        // Mock email log repo
        $or = $this->createMock(ObjectRepository::class);
        $or->method('findOneBy')->willReturn($this->dummyEmailLog);

        // Mock entity manager
        $this->emMock = $this->createMock(EntityManagerInterface::class);
        $this->emMock->method('getRepository')->willReturn($or);
        $this->emMock->method('persist')->willReturn(null);
        $this->emMock->method('flush')->willReturn(null);

        // Mock swift message
        $swiftMessageMock = $this->createMock(Swift_Mime_SimpleMessage::class);
        $swiftMessageMock->method('getId')->willReturn($this->dummyEmailLog->getMessageId());
        $swiftMessageMock->method('getFrom')->willReturn($this->dummyEmailLog->getEmailFrom());
        $swiftMessageMock->method('getTo')->willReturn($this->dummyEmailLog->getEmailTo());
        $swiftMessageMock->method('getSubject')->willReturn($this->dummyEmailLog->getSubject());
        $swiftMessageMock->method('toString')->willReturn($this->dummyEmailLog->getEml());

        // Mock swift send event
        $this->sendEvent = $this->createMock(Swift_Events_SendEvent::class);
        $this->sendEvent->method('getMessage')->willReturn($swiftMessageMock);
    }

    /**
     * @dataProvider exceptionProvider
     *
     * @param string $exception
     * @param string $class
     *
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotExistsException
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsInterfaceException
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ConfigParameterEmptyException
     */
    public function testExceptions(string $exception, string $class): void
    {
        $this->expectException($exception);

        $em = $this->createMock(EntityManagerInterface::class);

        new SendEmailListener($em, $class);
    }

    public function exceptionProvider(): array
    {
        return [
            'empty config parameter' => [ConfigParameterEmptyException::class, ''],
            // TODO: allow empty config, but then, check at all executions!
            'non existing class' => [ClassNotExistsException::class, 'WrongNamespace\EmailLog'],
            'class not implements interface' => [ClassNotImplementsInterfaceException::class, Exception::class],
        ];
    }

    /**
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotExistsException
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsInterfaceException
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ConfigParameterEmptyException
     */
    public function testBeforeSendPerformed(): void
    {
        // Set email status
        $this->sendEvent->method('getResult')->willReturn(Swift_Events_SendEvent::RESULT_PENDING);

        // Simulate new email send
        $listener = new SendEmailListener($this->emMock, ExampleEmailLogEntity::class);
        $listener->beforeSendPerformed($this->sendEvent);

        // Check if all data saved
        $newEmailLog = $listener->getEmailLog();
        self::assertEquals($this->dummyEmailLog->getMessageId(), $newEmailLog->getMessageId());
        self::assertEquals($this->dummyEmailLog->getEmailFrom(), $newEmailLog->getEmailFrom());
        self::assertEquals($this->dummyEmailLog->getEmailTo(), $newEmailLog->getEmailTo());
        self::assertEquals($this->dummyEmailLog->getSubject(), $newEmailLog->getSubject());
        self::assertEquals($this->dummyEmailLog->getEml(), $newEmailLog->getEml());
        self::assertEquals(Swift_Events_SendEvent::RESULT_PENDING, $newEmailLog->getResultStatus());
    }

    /**
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotExistsException
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsInterfaceException
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ConfigParameterEmptyException
     */
    public function testSendPerformed(): void
    {
        // Set email status
        $this->sendEvent->method('getResult')->willReturn(Swift_Events_SendEvent::RESULT_SUCCESS);

        // Simulate email update
        $listener = new SendEmailListener($this->emMock, ExampleEmailLogEntity::class);
        $listener->sendPerformed($this->sendEvent);

        // Check if data updated
        $newEmailLog = $listener->getEmailLog();
        self::assertEquals(Swift_Events_SendEvent::RESULT_SUCCESS, $newEmailLog->getResultStatus());
    }

    public function testExceptionThrown(): void
    {
    }

    // TODO: Test exceptionThrown

    // TODO: Test twice aufrufe (gleiche message id's! > sollte dann von lokale variable geladen werden)
    // TODO: Test twice emails (different message id's! > sollte dann von db geladen werden)
}
