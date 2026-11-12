<?php

namespace App\Storyblok\Transfers;

use Tlab\StoryblokTransfers\Transfers\RichtextTransfer;
use Tlab\TransferObjects\AbstractTransfer;

/**
 * !!! THIS TRANSFER CLASS FILE IS AUTO-GENERATED, CHANGES WILL BREAK YOUR PROJECT
 * !!! DO NOT CHANGE ANYTHING IN THIS FILE
 */
class PageTransfer extends AbstractTransfer
{
    /**
     * @var string|null
     */
    private ?string $title = null;

    /**
     * @var RichtextTransfer|null
     */
    private ?RichtextTransfer $body = null;

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     *
     * @return $this
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return RichtextTransfer|null
     */
    public function getBody(): ?RichtextTransfer
    {
        return $this->body;
    }

    /**
     * @param RichtextTransfer|null $body
     *
     * @return $this
     */
    public function setBody(?RichtextTransfer $body): self
    {
        $this->body = $body;

        return $this;
    }

}
