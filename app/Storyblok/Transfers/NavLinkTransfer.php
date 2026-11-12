<?php

namespace App\Storyblok\Transfers;

use Tlab\StoryblokTransfers\Transfers\LinkTransfer;
use Tlab\TransferObjects\AbstractTransfer;

/**
 * !!! THIS TRANSFER CLASS FILE IS AUTO-GENERATED, CHANGES WILL BREAK YOUR PROJECT
 * !!! DO NOT CHANGE ANYTHING IN THIS FILE
 */
class NavLinkTransfer extends AbstractTransfer
{
    /**
     * @var string|null
     */
    private ?string $label = null;

    /**
     * @var LinkTransfer|null
     */
    private ?LinkTransfer $link = null;

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string|null $label
     *
     * @return $this
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return LinkTransfer|null
     */
    public function getLink(): ?LinkTransfer
    {
        return $this->link;
    }

    /**
     * @param LinkTransfer|null $link
     *
     * @return $this
     */
    public function setLink(?LinkTransfer $link): self
    {
        $this->link = $link;

        return $this;
    }

}
