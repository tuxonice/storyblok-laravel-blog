<?php

namespace App\Storyblok\Transfers;

use Tlab\StoryblokTransfers\Transfers\AssetTransfer;
use Tlab\TransferObjects\AbstractTransfer;

/**
 * !!! THIS TRANSFER CLASS FILE IS AUTO-GENERATED, CHANGES WILL BREAK YOUR PROJECT
 * !!! DO NOT CHANGE ANYTHING IN THIS FILE
 */
class ImageFigureTransfer extends AbstractTransfer
{
    /**
     * @var AssetTransfer|null
     */
    private ?AssetTransfer $image = null;

    /**
     * @var string|null
     */
    private ?string $caption = null;

    /**
     * @return AssetTransfer|null
     */
    public function getImage(): ?AssetTransfer
    {
        return $this->image;
    }

    /**
     * @param AssetTransfer|null $image
     *
     * @return $this
     */
    public function setImage(?AssetTransfer $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCaption(): ?string
    {
        return $this->caption;
    }

    /**
     * @param string|null $caption
     *
     * @return $this
     */
    public function setCaption(?string $caption): self
    {
        $this->caption = $caption;

        return $this;
    }

}
