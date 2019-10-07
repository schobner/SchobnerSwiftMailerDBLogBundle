<?php

namespace Schobner\SwiftMailerDBLog\Modal;

use DateTime;
use Swift_Mime_SimpleMessage;

interface EmailLogInterface
{

    public function getId(): int;

    public function setId(int $id): self;

    public function getCreated(): ?DateTime;

    public function setCreated(DateTime $created): self;

    public function getUpdated(): ?DateTime;

    public function setUpdated(DateTime $updated): self;

    public function getMessageId(): string;

    public function setMessageId(string $messageId): self;

    public function getEmailFrom(): array;

    public function setEmailFrom(array $emailFrom): self;

    public function getEmailTo(): array;

    public function setEmailTo(array $emailTo): self;

    public function getSubject(): string;

    public function setSubject(string $subject): self;

    public function getEml(): string;

    public function setEml(string $eml): self;

    public function getResultStatus(): int;

    public function setResultStatus(int $resultStatus): self;

    public function getSendExceptionMessage(): ?string;

    public function setSendExceptionMessage(string $sendExceptionMessage): self;

    public function getSwiftMessage(): Swift_Mime_SimpleMessage;

    public function setSwiftMessage(Swift_Mime_SimpleMessage $swiftMessage): self;
}
