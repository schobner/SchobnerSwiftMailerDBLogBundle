<?php

namespace Schobner\SwiftMailerDBLogBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsInterfaceException;
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
     * @throws \Schobner\SwiftMailerDBLogBundle\Exception\ClassNotImplementsInterfaceException
     */
    public function __construct(EntityManagerInterface $em, string $email_log_class_name)
    {
        if (!empty($email_log_class_name) &&
            class_exists($email_log_class_name) &&
            !in_array(EmailLogInterface::class, class_implements($email_log_class_name), true)) {
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
        if (empty($this->emailLogEntityClassName) || !class_exists($this->emailLogEntityClassName)) {
            return;
        }

        $this->loadOrCreateEmailLog($evt->getMessage()->getId());
        $this->createLog($evt->getMessage(), $evt->getResult());
    }

    public function sendPerformed(Swift_Events_SendEvent $evt): void
    {
        if (empty($this->emailLogEntityClassName) || !class_exists($this->emailLogEntityClassName)) {
            return;
        }

        $this->loadOrCreateEmailLog($evt->getMessage()->getId());
        $this->updateLog($evt->getResult());
    }

    public function exceptionThrown(Swift_Events_TransportExceptionEvent $evt): void
    {
        if (empty($this->emailLogEntityClassName) || !class_exists($this->emailLogEntityClassName)) {
            return;
        }

        $this->loadOrCreateEmailLog($evt->getSource()->getMessage()->getId()); // FIXME: geht das überhaupt?!
        $this->updateLog(Swift_Events_SendEvent::RESULT_FAILED, $evt->getException()->getMessage());
    }

    private function loadOrCreateEmailLog(string $msg_id): void
    {
        // Already loaded
        if ($this->emailLog instanceof $this->emailLogEntityClassName) {
            return;
        }

        // Get message form database
        $emailLogRepo = $this->em->getRepository($this->emailLogEntityClassName);
        $this->emailLog = $emailLogRepo->findOneBy(['messageId' => $msg_id]);

        // Create new if not found
        if ($this->emailLog === null) {
            $this->emailLog = (new $this->emailLogEntityClassName());
            $this->emailLog->setMessageId($msg_id);
        }
    }

    private function createLog(Swift_Mime_SimpleMessage $msg, int $result_status): void
    {
        $this->emailLog
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
