<?php

namespace PhpNsFixer\Commands;

use Illuminate\Support\Collection;
use PhpNsFixer\Checker;
use PhpNsFixer\Finder;
use PhpNsFixer\Result;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;

class CheckCommand extends Command
{
    /**
     * @var ProgressBar
     */
    protected $progressBar;

    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('check')
            ->setDefinition([
                new InputArgument('path', InputArgument::REQUIRED, 'The path.'),
                new InputOption('prefix', 'P', InputOption::VALUE_REQUIRED, 'Namespace prefix.'),
                new InputOption('ignore-empty', 'E', InputOption::VALUE_NONE, 'Ignore files without namespace.')
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = Finder::discover($input->getArgument('path'));

        $this->progressStart($output, $files);

        $problematicFiles = $this->collectProblematicFiles($input, $files);

        $this->progressFinish($output);

        if ($problematicFiles->count() === 0) {
            $output->writeln("<info>No problems found! :)</info>");
            return;
        }

        $output->writeln(
            sprintf(
                "<options=bold,underscore>There %s %d wrong %s:</>\n",
                $problematicFiles->count() !== 1 ? 'are' : 'is',
                $problematicFiles->count(),
                $problematicFiles->count() !== 1 ? 'namespaces' : 'namespace'
            )
        );

        $problematicFiles
            ->each(function (Result $result, $key) use ($output) {
                $output->writeln(sprintf("%d) %s:", $key + 1, $result->file()->getRelativePathname()));
                $output->writeln(sprintf("\t<fg=red>- %s</>", $result->expected()));
                $output->writeln(sprintf("\t<fg=green>+ %s</>", $result->actual()));
            });
    }

    /**
     * @param InputInterface $input
     * @param Collection $files
     * @return Collection
     */
    protected function collectProblematicFiles(InputInterface $input, Collection $files): Collection
    {
        return $files
            ->map(function (SplFileInfo $file) use ($input) {
                $this->progressBar->setMessage($file->getRelativePathname(), 'filename');
                $this->progressBar->advance();

                $result = (new Checker($file))->check(
                    $input->getOption('prefix') ?? '',
                    $input->getOption('ignore-empty') ?? false
                );

                if ($result->isValid()) {
                    return null;
                }

                return $result;
            })
            ->filter()
            ->values();
    }

    /**
     * @param OutputInterface $output
     * @param Collection $files
     * @return void
     */
    protected function progressStart(OutputInterface $output, Collection $files): void
    {
        $this->progressBar = new ProgressBar($output, $files->count());

        $this->progressBar->setFormatDefinition('custom', 'Checking files... %current%/%max% (%filename%)');
        $this->progressBar->setFormat('custom');

        $this->progressBar->start();
    }

    /**
     * @param OutputInterface $output
     */
    protected function progressFinish(OutputInterface $output): void
    {
        $this->progressBar->setMessage('Done', 'filename');
        $this->progressBar->finish();

        $output->writeln("\n");
    }
}
