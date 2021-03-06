<?php

namespace PhpNsFixer\Tests\Finder;

use PhpNsFixer\Finder\FileFinder;
use PhpNsFixer\Tests\InteractsWithFiles;
use PhpNsFixer\Tests\TestCase;

class FileFinderTest extends TestCase
{
    use InteractsWithFiles;

    /** @test */
    public function lists_target_files()
    {
        @touch($this->joinPath([$this->testTempPath, '.git']));
        @touch($this->joinPath([$this->testTempPath, '.ignoreme.php']));
        @touch($this->joinPath([$this->testTempPath, 'bar.php']));
        @touch($this->joinPath([$this->testTempPath, 'baz.phpt']));
        @touch($this->joinPath([$this->testTempPath, 'ruby.rb']));
        @touch($this->joinPath([$this->testTempPath, 'python.py']));
        @touch($this->joinPath([$this->testTempPath, 'package.json']));
        @mkdir($this->joinPath([$this->testTempPath, 'vendor']));
        @touch($this->joinPath([$this->testTempPath, 'vendor/autoload.php']));

        $files = FileFinder::list($this->testTempPath);

        $this->assertEquals(2, $files->count());
        $this->assertEquals($this->joinPath([$this->testTempPath, 'bar.php']), $files->keys()->get(0));
        $this->assertEquals($this->joinPath([$this->testTempPath, 'baz.phpt']), $files->keys()->get(1));
    }
}
