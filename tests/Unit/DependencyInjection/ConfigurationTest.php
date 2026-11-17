<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Softspring\CmsSeoPlugin\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testWordStatisticsOverridesRetainUnspecifiedDefaults(): void
    {
        $config = new Processor()->processConfiguration(new Configuration(), [
            ['word_statistics' => ['weights' => ['strong' => 7], 'stop_words' => ['es' => ['ejemplo']], 'limit' => 10]],
        ]);
        self::assertSame(10, $config['word_statistics']['limit']);
        self::assertSame(6, $config['word_statistics']['weights']['h1']);
        self::assertSame(7, $config['word_statistics']['weights']['strong']);
        self::assertSame(['ejemplo'], $config['word_statistics']['stop_words']['es']);
        self::assertTrue($config['site_analysis']['enabled']);
    }

    public function testOutOfRangeWeightIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new Processor()->processConfiguration(new Configuration(), [
            ['word_statistics' => ['weights' => ['strong' => 21]]],
        ]);
    }
}
