<?php

namespace PhpNsFixer\Console;

use PhpNsFixer\Event\FileProcessedEvent;
use PhpNsFixer\Finder\FileFinder;
use PhpNsFixer\Fixer\Result;
use PhpNsFixer\Runner\Runner;
use PhpNsFixer\Runner\RunnerOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tightenco\Collect\Support\Collection;

class FixCommand extends Command
{
    /**
     * @var ProgressBar
     */
    private $progressBar;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addListener(FileProcessedEvent::class, function (FileProcessedEvent $event) {
            $this->progressBar->setMessage($event->getFile()->getRelativePathname(), 'filename');
            $this->progressBar->advance();
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('fix');
        $this->setDefinition([
            new InputArgument('path', InputArgument::REQUIRED, 'The path.'),
            new InputOption('prefix', 'P', InputOption::VALUE_REQUIRED, 'Namespace prefix.'),
            new InputOption('skip-empty', 'E', InputOption::VALUE_NONE, 'Skip files without namespace.'),
            new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Only show which files would have been modified.')
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = FileFinder::list($input->getArgument('path'));

        $this->progressStart($output, $files);

        $runnerOptions = new RunnerOptions(
            $files,
            $input->getOption('prefix'),
            $input->getOption('skip-empty'),
            $input->getOption('dry-run')
        );
        $problematicFiles = (new Runner($runnerOptions, $this->dispatcher))->run();

        $this->progressFinish($output);

        if ($problematicFiles->count() === 0) {
            $output->writeln("<info>No problems found! :)</info>");
            return 0;
        }

        $output->writeln(
            sprintf(
                "<options=bold,underscore>There %s %d wrong %s:</>\n",
                $problematicFiles->count() !== 1 ? ($input->getOption('dry-run') ? 'are' : 'were') : ($input->getOption('dry-run') ? 'is' : 'was'),
                $problematicFiles->count(),
                $problematicFiles->count() !== 1 ? 'namespaces' : 'namespace'
            )
        );

        $problematicFiles->each(function (Result $result, $key) use ($output) {
            $output->writeln(sprintf("%d) %s:", $key + 1, $result->getFile()->getRelativePathname()));
            $output->writeln(sprintf("\t<fg=red>- %s</>", $result->getExpected()));
            $output->writeln(sprintf("\t<fg=green>+ %s</>", $result->getActual()));
        });

        return $problematicFiles->count();
    }

    /**
     * @param OutputInterface $output
     * @param Collection $files
     * @return void
     */
    private function progressStart(OutputInterface $output, Collection $files): void
    {
        $this->progressBar = new ProgressBar($output, $files->count());

        $this->progressBar->setFormatDefinition('custom', 'Checking files... %current%/%max% (%filename%)');
        $this->progressBar->setFormat('custom');

        $this->progressBar->start();
    }

    /**
     * @param OutputInterface $output
     */
    private function progressFinish(OutputInterface $output): void
    {
        $this->progressBar->setMessage('Done', 'filename');
        $this->progressBar->finish();

        $output->writeln("\n");
    }
}