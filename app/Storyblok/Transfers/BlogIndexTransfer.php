<?php

namespace App\Storyblok\Transfers;

use Tlab\TransferObjects\AbstractTransfer;

/**
 * !!! THIS TRANSFER CLASS FILE IS AUTO-GENERATED, CHANGES WILL BREAK YOUR PROJECT
 * !!! DO NOT CHANGE ANYTHING IN THIS FILE
 */
class BlogIndexTransfer extends AbstractTransfer
{
    /**
     * @var string|null
     */
    private ?string $intro = null;

    /**
     * @var string|null
     */
    private ?string $featuredPost = null;

    /**
     * @var float|null
     */
    private ?float $postsPerPage = null;

    /**
     * @return string|null
     */
    public function getIntro(): ?string
    {
        return $this->intro;
    }

    /**
     * @param string|null $intro
     *
     * @return $this
     */
    public function setIntro(?string $intro): self
    {
        $this->intro = $intro;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFeaturedPost(): ?string
    {
        return $this->featuredPost;
    }

    /**
     * @param string|null $featuredPost
     *
     * @return $this
     */
    public function setFeaturedPost(?string $featuredPost): self
    {
        $this->featuredPost = $featuredPost;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPostsPerPage(): ?float
    {
        return $this->postsPerPage;
    }

    /**
     * @param float|null $postsPerPage
     *
     * @return $this
     */
    public function setPostsPerPage(?float $postsPerPage): self
    {
        $this->postsPerPage = $postsPerPage;

        return $this;
    }

}
