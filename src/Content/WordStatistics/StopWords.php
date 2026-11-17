<?php

declare(strict_types=1);

namespace Softspring\CmsSeoPlugin\Content\WordStatistics;

class StopWords
{
    private const WORDS = [
        'en' => 'a an and are as at be been being but by can could did do does doing for from had has have having he her here hers herself him himself his how i if in into is it its itself just me more most my myself no nor not of on once only or other our ours ourselves out over own same she should so some such than that the their theirs them themselves then there these they this those through to too under until up very was we were what when where which while who whom why will with would you your yours yourself yourselves',
        'es' => 'a al algo alguna algunas alguno algunos ante antes aquel aquella aquellas aquellos aquí así aun aunque bajo bien cada casi como con contra cual cuales cualquier cuando cuanto de del desde donde dos durante e el ella ellas ello ellos en entre era erais eran eras eres es esa esas ese eso esos esta estaba estaban estar estas este esto estos fue fueron ha haber había han hasta hay la las le les lo los más me mi mis misma mismo muchas mucho muchos muy nada ni no nos nosotros nuestra nuestro nuestros o os otra otras otro otros para pero poco por porque pues que qué quien quienes se sea según ser si sí sido sin sino sobre sois solo somos son su sus tal también tan tanto te tener tengo ti tiene tienen todo todos tras tu tus tú un una unas uno unos usted ustedes va vamos vez ya yo',
    ];

    public function forLocale(string $locale, array $additional = []): array
    {
        $language = strtolower(explode('-', str_replace('_', '-', $locale))[0]);
        $words = explode(' ', self::WORDS[$language] ?? '');
        $words = array_merge($words, $additional[$language] ?? [], $additional[$locale] ?? []);

        return array_fill_keys(array_map(static fn(string $word): string => mb_strtolower(trim($word), 'UTF-8'), $words), true);
    }

    public function supports(string $locale): bool
    {
        return isset(self::WORDS[strtolower(explode('-', str_replace('_', '-', $locale))[0])]);
    }
}
