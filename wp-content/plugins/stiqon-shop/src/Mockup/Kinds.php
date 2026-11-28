<?php

namespace Stiqon\Shop\Mockup;

use InvalidArgumentException;

/**
 * The kinds of products the shop can draw, and how their names give them away.
 *
 * A product's kind is read from its name, then from the names of its
 * categories (Catalog\Guess::type()): the first text that names a kind
 * decides, and within one text the kinds are asked in the order listed here.
 * The order matters because German builds words from words: a
 * "Plakatständer" is a stand, not a poster; a "Postkartenkalender" is a
 * calendar; a "Papiertragetasche" is a paper bag, not a cotton one.
 *
 * Patterns are matched at the start of a word (see Guess), so a kind that is
 * also the end of longer words says so: "[a-zäöüß]*kalender" finds
 * "Wandkalender" and "Tischkalender".
 */
final class Kinds
{
    private const PREFIX = '[a-zäöüß]*';

    /** type => [class, pattern] */
    private const ALL = [
        'sticker' => [Sticker::class, self::PREFIX.'sticker|'.self::PREFIX.'aufkleber|'.self::PREFIX.'etikett|label'],
        'calendar' => [Calendar::class, self::PREFIX.'kalender|'.self::PREFIX.'planer'],
        'folder' => [Folder::class, self::PREFIX.'mappe'],
        'card' => [Card::class, self::PREFIX.'karte'],
        'leaflet' => [Leaflet::class, 'faltbl[aä]tt|falzflyer|leporello|'.self::PREFIX.'falz'],
        'flyer' => [Flyer::class, self::PREFIX.'flyer|flugbl[aä]tt|handzettel'],
        'envelope' => [Envelope::class, self::PREFIX.'umschl[aä]g|kuvert|versandtasche'],
        'letter' => [Letter::class, 'briefpapier|'.self::PREFIX.'briefb[oö]gen|gesch[aä]ftspapier'],
        'notepad' => [Notepad::class, self::PREFIX.'block|'.self::PREFIX.'bl[oö]cke|haftnoti|'.self::PREFIX.'notiz|durchschreib'],
        'brochure' => [Brochure::class, self::PREFIX.'brosch[uü]re|katalog|magazin|'.self::PREFIX.'zeitung|'.self::PREFIX.'hefte?(?![a-zäöüß])'],
        'standee' => [Standee::class, 'kundenstopper|plakatst[aä]nder|'.self::PREFIX.'aufsteller'],
        'poster' => [Poster::class, self::PREFIX.'plakat|'.self::PREFIX.'poster'],
        'rollup' => [Rollup::class, 'roll-?ups?(?![a-zäöüß])'],
        'banner' => [Banner::class, self::PREFIX.'banner|'.self::PREFIX.'plane(?![a-zäöüß])|'.self::PREFIX.'planen'],
        'box' => [Box::class, self::PREFIX.'karton|'.self::PREFIX.'schachtel|'.self::PREFIX.'box(?!er)|'.self::PREFIX.'verpackung'],
        'bag' => [Bag::class, 'papiert[uü]te|papiertrage|'.self::PREFIX.'t[uü]te|tragetasche'],
        // Garments before bags: a hoodie may have a "Kängurubeutel", a shirt is "aus Baumwolle".
        'shirt' => [Shirt::class, 't-?shirt|'.self::PREFIX.'shirt|hoodie|kapuzen|pullover|sweat|polo'],
        'tote' => [Tote::class, '(?:baumwoll|stoff|canvas)-?(?:trage)?(?:tasche|beutel)|jute|'.self::PREFIX.'beutel|einkaufstasche'],
        'mug' => [Mug::class, self::PREFIX.'tasse|'.self::PREFIX.'becher'],
        'bottle' => [Bottle::class, self::PREFIX.'flasche'],
        'coaster' => [Coaster::class, 'bierdeckel|'.self::PREFIX.'untersetzer|'.self::PREFIX.'deckel'],
    ];

    /**
     * The kinds the plugin can draw, in the order their names are checked.
     *
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::available());
    }

    public static function has(string $type): bool
    {
        return isset(self::available()[$type]);
    }

    /**
     * The class that draws a kind.
     *
     * @return class-string<Kind>
     */
    public static function of(string $type): string
    {
        return self::available()[$type][0] ?? throw new InvalidArgumentException("Unknown kind of product [{$type}].");
    }

    /**
     * What names this kind in a product's or category's name (see the class comment).
     */
    public static function pattern(string $type): string
    {
        return self::available()[$type][1] ?? throw new InvalidArgumentException("Unknown kind of product [{$type}].");
    }

    /**
     * What the administration calls each kind, in the order of types().
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_map(static fn (array $kind): string => $kind[0]::label(), self::available());
    }

    /**
     * The drawing of a kind for a spec, as SVG markup.
     *
     * @param  array<string, mixed>  $spec
     */
    public static function draw(string $type, array $spec, string $class = ''): string
    {
        return self::of($type)::fromArray($spec)->render($class);
    }

    /**
     * The kinds whose class is there. A kind is listed before its drawing
     * exists while the drawings are being built; until then it is not offered.
     *
     * @return array<string, array{0: class-string<Kind>, 1: string}>
     */
    private static function available(): array
    {
        static $available = null;

        return $available ??= array_filter(
            self::ALL,
            static fn (array $kind): bool => class_exists($kind[0]) && is_subclass_of($kind[0], Kind::class),
        );
    }
}
