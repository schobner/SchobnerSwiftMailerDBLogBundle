<?php

namespace Schobner\SwiftMailerDBLogBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Schobner\SwiftMailerDBLogBundle\Exception\ClassNotExistsException;
use Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsInterfaceException;
use Schobner\SwiftMailerDBLogBundle\Exception\ConfigParameterEmptyException;
use Schobner\SwiftMailerDBLogBundle\Model\EmailLogInterface;
use Swift_Events_SendEvent;
use Swift_Events_SendListener;
use Swift_Events_TransportExceptionEvent;
use Swift_Events_TransportExceptionListener;
use Swift_Mime_SimpleMessage;

class SendEmailListener implements Swift_Events_SendListener, Swift_Events_TransportExceptionListener
{

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $em;

    /** @var \Schobner\SwiftMailerDBLogBundle\Model\EmailLog */
    private $emailLog;

    /** @var string */
    private $emailLogEntityClassName;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param string $email_log_class_name
     *
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotExistsException
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsInterfaceException
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ConfigParameterEmptyException
     */
    public function __construct(EntityManagerInterface $em, string $email_log_class_name)
    {
        if (empty($email_log_class_name)) {
            throw new ConfigParameterEmptyException('Set email_log_class_name in your config.yml.');
        }

        if (!class_exists($email_log_class_name)) {
            throw new ClassNotExistsException('Check email_log_class_name in your config.yml.');
        }

        if (!in_array(EmailLogInterface::class, class_implements($email_log_class_name), true)) {
            throw new ClassNotImplementsInterfaceException(
                'Set a class in email_log_class_name which extends \Schobner\SwiftMailerDBLogBundle\Model\EmailLog.'
            );
        }

        $this->em = $em;
        $this->emailLogEntityClassName = $email_log_class_name;
    }

    /**
     * Function for unit tests
     *
     * @return \Schobner\SwiftMailerDBLogBundle\Model\EmailLogInterface|null
     */
    public function getEmailLog(): ?EmailLogInterface
    {
        return $this->emailLog;
    }

    public function beforeSendPerformed(Swift_Events_SendEvent $evt): void
    {
        $this->createLog($evt->getMessage(), $evt->getResult());
    }

    public function sendPerformed(Swift_Events_SendEvent $evt): void
    {
        $this->loadEmailLog($evt->getMessage(), $evt->getResult());
        $this->updateLog($evt->getResult());
    }

    public function exceptionThrown(Swift_Events_TransportExceptionEvent $evt): void
    {
        $this->loadEmailLog($evt->getSource()->getMessage(), Swift_Events_SendEvent::RESULT_FAILED);
        // FIXME: geht das überhaupt?!

        $this->updateLog(Swift_Events_SendEvent::RESULT_FAILED, $evt->getException()->getMessage());
    }

    private function createLog(Swift_Mime_SimpleMessage $msg, int $result_status): void
    {
        $this->emailLog = (new $this->emailLogEntityClassName());
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

    private function loadEmailLog(Swift_Mime_SimpleMessage $msg, int $result_status): void
    {
        // Already loaded
        if ($this->emailLog !== null && $this->emailLog->getMessageId() === $msg->getId()) {
            return;
        }

        // Get message form database
        $this->emailLog = $this->em->getRepository($this->emailLogEntityClassName)->findOneBy(['message_id' => $msg->getId()]);

        // Create new if not found
        if ($this->emailLog === null) {
            $this->createLog($msg, $result_status);
        }
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
