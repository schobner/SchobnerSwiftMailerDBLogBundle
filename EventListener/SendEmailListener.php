<?php

namespace Schobner\SwiftMailerDBLogBundle\EventListener;

use Schobner\SwiftMailerDBLogBundle\Modal\EmailLog;
use Doctrine\ORM\EntityManagerInterface;
use Swift_Events_SendEvent;
use Swift_Events_SendListener;
use Swift_Events_TransportExceptionEvent;
use Swift_Events_TransportExceptionListener;
use Swift_Mime_SimpleMessage;

class SendEmailListener implements Swift_Events_SendListener, Swift_Events_TransportExceptionListener
{
    // TODO:GN: Unittests erstellen.

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $em;

    /** @var \Schobner\SwiftMailerDBLogBundle\Modal\EmailLog */
    private $emailLog;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

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

    private function createLog(Swift_Mime_SimpleMessage $msg, int $result_status): void
    {
        $this->emailLog = (new EmailLog())
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
