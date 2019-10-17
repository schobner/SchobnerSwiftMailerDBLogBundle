<?php

namespace Schobner\SwiftMailerDBLogBundle\Model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Swift_Mime_SimpleMessage;

/**
 * @ORM\Entity()
 * @ORM\Table(name="swift_mailer_log")
 *
 * @UniqueEntity("message_id")
 */
abstract class EmailLog implements EmailLogInterface
{

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true)
     */
    protected $messageId;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $emailFrom;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $emailReplyTo;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $emailTo;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $emailCc;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $emailBcc;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $subject;

    /**
     * The complete email as eml
     *
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $eml;

    /**
     * Status if message has send.
     * Show \Swift_Events_SendEvent::RESULT_...
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $resultStatus;

    /**
     * Errors from sending email
     *
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $sendExceptionMessage;

    /**
     * Original swift message. For retrospective bug fixes.
     *
     * @var \Swift_Mime_SimpleMessage
     *
     * @ORM\Column(type="text")
     */
    protected $swiftMessage;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): EmailLogInterface
    {
        $this->id = $id;

        return $this;
    }

    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): EmailLogInterface
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTime
    {
        return $this->updated;
    }

    public function setUpdated(DateTime $updated): EmailLogInterface
    {
        $this->updated = $updated;

        return $this;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function setMessageId(string $messageId): EmailLogInterface
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getEmailFrom(): array
    {
        return $this->emailFrom;
    }

    public function setEmailFrom(array $emailFrom): EmailLogInterface
    {
        $this->emailFrom = $emailFrom;

        return $this;
    }

    public function getEmailReplyTo(): string
    {
        return $this->emailReplyTo;
    }

    public function setEmailReplyTo(?string $emailReplyTo): EmailLogInterface
    {
        $this->emailReplyTo = $emailReplyTo;

        return $this;
    }

    public function getEmailTo(): array
    {
        return $this->emailTo;
    }

    public function setEmailTo(array $emailTo): EmailLogInterface
    {
        $this->emailTo = $emailTo;

        return $this;
    }

    public function getEmailCc(): array
    {
        return $this->emailCc;
    }

    public function setEmailCc(?array $emailCc): EmailLogInterface
    {
        $this->emailCc = $emailCc;

        return $this;
    }

    public function getEmailBcc(): array
    {
        return $this->emailBcc;
    }

    public function setEmailBcc(?array $emailBcc): EmailLogInterface
    {
        $this->emailBcc = $emailBcc;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): EmailLogInterface
    {
        $this->subject = $subject;

        return $this;
    }

    public function getEml(): string
    {
        return base64_decode($this->eml);
    }

    public function setEml(string $eml): EmailLogInterface
    {
        $this->eml = base64_encode($eml);

        return $this;
    }

    public function getResultStatus(): int
    {
        return $this->resultStatus;
    }

    public function setResultStatus(int $resultStatus): EmailLogInterface
    {
        $this->resultStatus = $resultStatus;

        return $this;
    }

    public function getSendExceptionMessage(): ?string
    {
        return $this->sendExceptionMessage;
    }

    public function setSendExceptionMessage(string $sendExceptionMessage): EmailLogInterface
    {
        $this->sendExceptionMessage = $sendExceptionMessage;

        return $this;
    }

    public function getSwiftMessage(): Swift_Mime_SimpleMessage
    {
        return unserialize($this->swiftMessage, [Swift_Mime_SimpleMessage::class]);
    }

    public function setSwiftMessage(Swift_Mime_SimpleMessage $swiftMessage): EmailLogInterface
    {
        $this->swiftMessage = serialize($swiftMessage);

        return $this;
    }
}
