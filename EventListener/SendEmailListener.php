<?php

namespace Schobner\SwiftMailerDBLogBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Schobner\SwiftMailerDBLog\Modal\EmailLogInterface;
use Schobner\SwiftMailerDBLogBundle\Exception\ClassNotExistsException;
use Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsEmailLogInterfaceException;
use Swift_Events_SendEvent;
use Swift_Events_SendListener;
use Swift_Events_TransportExceptionEvent;
use Swift_Events_TransportExceptionListener;
use Swift_Mime_SimpleMessage;
use Symfony\Component\DependencyInjection\Container;

class SendEmailListener implements Swift_Events_SendListener, Swift_Events_TransportExceptionListener
{

    // TODO:GN: Unittests erstellen.

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $em;

    /** @var \Schobner\SwiftMailerDBLogBundle\Modal\EmailLog */
    private $emailLog;

    /** @var string */
    private $emailLogClass;

    public function __construct(EntityManagerInterface $em, Container $container)//, string $emailLogClass)
    {
        $this->em = $em;
        //$this->emailLogClass = $emailLogClass;
        echo 'parameter from container: ';
        var_dump($container->getParameter('schobner_swift_mailer_db_log.email_log_entity'));
        var_dump($container->getParameter('email_log_entity'));
        $this->emailLogClass = $container->getParameter('schobner_swift_mailer_db_log.email_log_entity');
    }

    /**
     * @param \Swift_Events_SendEvent $evt
     *
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotExistsException
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsEmailLogInterfaceException
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $evt): void
    {
        // If email log already created
        if ($this->emailLog !== null) {
            return;
        }

        $this->createLog($evt->getMessage(), $evt->getResult());
    }

    public function sendPerformed(Swift_Events_SendEvent $evt): void
    {
        $this->updateLog($evt->getResult());
    }

    public function exceptionThrown(Swift_Events_TransportExceptionEvent $evt): void
    {
        $this->updateLog(Swift_Events_SendEvent::RESULT_FAILED, $evt->getException()->getMessage());
    }

    /**
     * @param \Swift_Mime_SimpleMessage $msg
     * @param int $result_status
     *
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotExistsException
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsEmailLogInterfaceException
     */
    private function createLog(Swift_Mime_SimpleMessage $msg, int $result_status): void
    {
        if (empty($this->emailLogClass)) {
            return;
        }

        if (!class_exists($this->emailLogClass)) {
            throw new ClassNotExistsException('Set email_log_entity in your config.yml.');
        }

        if (!in_array(EmailLogInterface::class, class_implements($this->emailLogClass), true)) {
            throw new ClassNotImplementsEmailLogInterfaceException(
                'Set a class in email_log_entity which extends \Schobner\SwiftMailerDBLogBundle\Modal\EmailLog.'
            );
        }

        $this->emailLog = (new $this->emailLogClass());
        $this->emailLog
            ->setMessageId($msg->getId())
            ->setEmailFrom($msg->getFrom())
            ->setEmailTo($msg->getTo())
            ->setSubject($msg->getSubject())
            ->setEml($msg->toString())
            ->setResultStatus($result_status)
            ->setSwiftMessage($msg);
        $this->em->persist($this->emailLog);
        $this->em->flush();
    }

    private function updateLog(int $result_status, string $send_exception_message = null): void
    {
        $this->emailLog->setResultStatus($result_status);
        if ($send_exception_message !== null) {
            $this->emailLog->setSendExceptionMessage($send_exception_message);
        }
        $this->em->persist($this->emailLog);
        $this->em->flush();
    }
}
