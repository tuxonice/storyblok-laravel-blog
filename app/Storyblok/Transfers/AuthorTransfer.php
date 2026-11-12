<?php

namespace App\Storyblok\Transfers;

use Tlab\StoryblokTransfers\Transfers\AssetTransfer;
use Tlab\StoryblokTransfers\Transfers\LinkTransfer;
use Tlab\TransferObjects\AbstractTransfer;

/**
 * !!! THIS TRANSFER CLASS FILE IS AUTO-GENERATED, CHANGES WILL BREAK YOUR PROJECT
 * !!! DO NOT CHANGE ANYTHING IN THIS FILE
 */
class AuthorTransfer extends AbstractTransfer
{
    /**
     * @var string|null
     */
    private ?string $name = null;

    /**
     * @var string|null
     */
    private ?string $role = null;

    /**
     * @var string|null
     */
    private ?string $bio = null;

    /**
     * @var AssetTransfer|null
     */
    private ?AssetTransfer $avatar = null;

    /**
     * @var LinkTransfer|null
     */
    private ?LinkTransfer $website = null;

    /**
     * @var string|null
     */
    private ?string $github = null;

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     *
     * @return $this
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * @param string|null $role
     *
     * @return $this
     */
    public function setRole(?string $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBio(): ?string
    {
        return $this->bio;
    }

    /**
     * @param string|null $bio
     *
     * @return $this
     */
    public function setBio(?string $bio): self
    {
        $this->bio = $bio;

        return $this;
    }

    /**
     * @return AssetTransfer|null
     */
    public function getAvatar(): ?AssetTransfer
    {
        return $this->avatar;
    }

    /**
     * @param AssetTransfer|null $avatar
     *
     * @return $this
     */
    public function setAvatar(?AssetTransfer $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return LinkTransfer|null
     */
    public function getWebsite(): ?LinkTransfer
    {
        return $this->website;
    }

    /**
     * @param LinkTransfer|null $website
     *
     * @return $this
     */
    public function setWebsite(?LinkTransfer $website): self
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getGithub(): ?string
    {
        return $this->github;
    }

    /**
     * @param string|null $github
     *
     * @return $this
     */
    public function setGithub(?string $github): self
    {
        $this->github = $github;

        return $this;
    }

}
