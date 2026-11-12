<?php

namespace App\Storyblok\Transfers;

use Tlab\StoryblokTransfers\Transfers\AssetTransfer;
use Tlab\StoryblokTransfers\Transfers\BlokTransfer;
use Tlab\TransferObjects\AbstractTransfer;

/**
 * !!! THIS TRANSFER CLASS FILE IS AUTO-GENERATED, CHANGES WILL BREAK YOUR PROJECT
 * !!! DO NOT CHANGE ANYTHING IN THIS FILE
 */
class SiteSettingsTransfer extends AbstractTransfer
{
    /**
     * @var string|null
     */
    private ?string $siteName = null;

    /**
     * @var array<BlokTransfer>
     */
    private array $nav = [];

    /**
     * @var array<BlokTransfer>
     */
    private array $footerLinks = [];

    /**
     * @var AssetTransfer|null
     */
    private ?AssetTransfer $defaultOgImage = null;

    /**
     * @return string|null
     */
    public function getSiteName(): ?string
    {
        return $this->siteName;
    }

    /**
     * @param string|null $siteName
     *
     * @return $this
     */
    public function setSiteName(?string $siteName): self
    {
        $this->siteName = $siteName;

        return $this;
    }

    /**
     * @return array<BlokTransfer>
     */
    public function getNav(): array
    {
        return $this->nav;
    }

    /**
     * @param array<BlokTransfer> $nav
     *
     * @return $this
     */
    public function setNav(array $nav): self
    {
        $this->nav = $nav;

        return $this;
    }

    /**
     * @param BlokTransfer $navItem
     *
     * @return $this
     */
    public function addNavItem(BlokTransfer $navItem): self
    {
        $this->nav[] = $navItem;

        return $this;
    }
    /**
     * @return array<BlokTransfer>
     */
    public function getFooterLinks(): array
    {
        return $this->footerLinks;
    }

    /**
     * @param array<BlokTransfer> $footerLinks
     *
     * @return $this
     */
    public function setFooterLinks(array $footerLinks): self
    {
        $this->footerLinks = $footerLinks;

        return $this;
    }

    /**
     * @param BlokTransfer $footerLink
     *
     * @return $this
     */
    public function addFooterLink(BlokTransfer $footerLink): self
    {
        $this->footerLinks[] = $footerLink;

        return $this;
    }
    /**
     * @return AssetTransfer|null
     */
    public function getDefaultOgImage(): ?AssetTransfer
    {
        return $this->defaultOgImage;
    }

    /**
     * @param AssetTransfer|null $defaultOgImage
     *
     * @return $this
     */
    public function setDefaultOgImage(?AssetTransfer $defaultOgImage): self
    {
        $this->defaultOgImage = $defaultOgImage;

        return $this;
    }

}
