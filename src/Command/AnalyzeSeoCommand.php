<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Command;

use Softspring\CmsSeoPlugin\Seo\SeoAnalysisDispatcherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'sfs:cms:seo:analyze', description: 'Analyze CMS SEO data for sites and published content.')]
class AnalyzeSeoCommand extends Command
{
    public function __construct(
        private readonly SeoAnalysisDispatcherInterface $dispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('sites', null, InputOption::VALUE_NONE, 'Analyze configured CMS sites.')
            ->addOption('contents', null, InputOption::VALUE_NONE, 'Analyze published CMS content URLs.')
            ->addOption('async', null, InputOption::VALUE_NONE, 'Reserved for projects that wire this command to Messenger.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $analyzeSites = (bool) $input->getOption('sites');
        $analyzeContents = (bool) $input->getOption('contents');
        if (!$analyzeSites && !$analyzeContents) {
            $analyzeSites = true;
            $analyzeContents = true;
        }

        $result = $this->dispatcher->analyze($analyzeSites, $analyzeContents, (bool) $input->getOption('async'));

        if ($input->getOption('async') && false === $result['async']) {
            $io->warning('Async SEO analysis is project-specific. The default dispatcher runs synchronously; projects can replace SeoAnalysisDispatcherInterface with a Messenger-backed service.');
        }

        if (\is_array($result['sites'])) {
            $io->success(sprintf('Analyzed %d CMS site(s).', $result['sites']['count']));
        }

        if (\is_array($result['contents'])) {
            $summary = $result['contents'];
            $io->success(sprintf(
                'Analyzed %d content item(s), %d URL(s), %d error(s), %d warning(s).',
                $summary['contents'],
                $summary['urls'],
                $summary['errors'],
                $summary['warnings'],
            ));
        }

        return Command::SUCCESS;
    }
}
