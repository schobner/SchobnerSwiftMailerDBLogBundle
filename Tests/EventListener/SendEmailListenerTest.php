<?php

namespace Schobner\SwiftMailerDBLogBundle\Tests\EventListener;

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

    /** @var \Faker\Generator */
    private $faker;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->faker = Factory::create();
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

        // Mock entity manager
        $em = $this->createMock(EntityManagerInterface::class);

        new SendEmailListener($em, $class);
    }

    public function exceptionProvider(): array
    {
        return [
            'empty config parameter' => [ConfigParameterEmptyException::class, ''],
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
        // Example email content
        $messageId = $this->faker->regexify('[a-z0-9]{32}').'@swift.generated';
        $from = [$this->faker->email => $this->faker->company];
        $to = [$this->faker->email => $this->faker->firstName.' '.$this->faker->lastName];
        $subject = 'Test subject text';
        $eml = 'Binary message content';

        // Mock entity manager
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturn(null);
        $em->method('flush')->willReturn(null);

        // Mock message
        $message = $this->createMock(Swift_Mime_SimpleMessage::class);
        $message->method('getId')->willReturn($messageId);
        $message->method('getFrom')->willReturn($from);
        $message->method('getTo')->willReturn($to);
        $message->method('getSubject')->willReturn($subject);
        $message->method('toString')->willReturn($eml);

        // Mock send event
        $sendEvent = $this->createMock(Swift_Events_SendEvent::class);
        $sendEvent->method('getMessage')->willReturn($message);
        $sendEvent->method('getResult')->willReturn(Swift_Events_SendEvent::RESULT_SUCCESS);

        // Execute event listener
        $listener = new SendEmailListener($em, ExampleEmailLogEntity::class);
        $listener->beforeSendPerformed($sendEvent);

        // Check if data saved
        $emailLog = $listener->getEmailLog();
        self::assertEquals($messageId, $emailLog->getMessageId());
        self::assertEquals($from, $emailLog->getEmailFrom());
        self::assertEquals($to, $emailLog->getEmailTo());
        self::assertEquals($subject, $emailLog->getSubject());
        self::assertEquals($eml, $emailLog->getEml());
        self::assertEquals(Swift_Events_SendEvent::RESULT_SUCCESS, $emailLog->getResultStatus());
    }

    // Test if log created (all parameters?!)

    // Test already created

    // Test update (sendPerformed)

    // Test update (exceptionThrown)
}
