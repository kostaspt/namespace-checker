<?php

namespace PhpNsFixer\Fixer;

use Symfony\Component\Finder\SplFileInfo;

final class Result
{
    /**
     * @var SplFileInfo
     */
    private $file;

    /**
     * @var bool
     */
    private $valid;

    /**
     * @var string
     */
    private $actual;

    /**
     * @var string
     */
    private $expected;

    /**
     * @param SplFileInfo $file
     * @param bool $valid
     * @param string $actual
     * @param string $expected
     */
    public function __construct(SplFileInfo $file, bool $valid = false, string $actual = '', string $expected = '')
    {
        $this->file = $file;
        $this->valid = $valid;
        $this->actual = $actual;
        $this->expected = $expected;
    }

    /**
     * @return SplFileInfo
     */
    public function getFile(): SplFileInfo
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getActual(): string
    {
        return $this->actual;
    }

    /**
     * @return string
     */
    public function getExpected(): string
    {
        return $this->expected;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }
}
