<?php

namespace Stiqon\Shop\Mockup;

/**
 * A kind of product the shop can draw: a sticker, a flyer, a business card …
 *
 * Every kind is one class with a template under templates/mockup/. It draws
 * from a "spec" – the values listed in keys() – and shows a sample motif that
 * the visitor's own motif replaces in the browser. Kinds are listed in Kinds.
 */
interface Kind
{
    /** What the administration calls this kind ("Visitenkarte"). */
    public static function label(): string;

    /**
     * What the drawing understands: key => allowed values, or null for a switch
     * (true or false). Values stored on a product or on one of its options are
     * kept only when they are listed here (see Catalog\Spec::known()).
     *
     * @return array<string, list<string>|null>
     */
    public static function keys(): array;

    /**
     * The one value that sets the product's outline – a sticker's shape, a
     * flyer's format – with its German names, for the administration. "key"
     * is one of keys().
     *
     * @return array{key: string, label: string, values: array<string, string>}
     */
    public static function form(): array;

    /**
     * Names of the sample motifs this kind is drawn with.
     *
     * @return list<string>
     */
    public static function motifs(): array;

    /**
     * How the names of products and of their options say what the drawing
     * looks like (see Catalog\Guess). Patterns are regular expressions without
     * delimiters, matched case-insensitively at the start of a word: "rund"
     * finds "Runde", not "abgerundet". The first value whose pattern matches wins.
     *
     *   form:     value of the form key => pattern in the product's name
     *   options:  key => (value => pattern) in the name of a chosen option
     *   switches: key => [pattern for true, pattern for false] in the name of a chosen option
     *
     * @return array{form: array<string, string>, options: array<string, array<string, string>>, switches: array<string, array{0: string, 1: string}>}
     */
    public static function names(): array;

    /**
     * The drawing for a spec. Keys that are not this kind's are ignored;
     * unknown values of its own keys throw an InvalidArgumentException.
     *
     * @param  array<string, mixed>  $spec
     */
    public static function fromArray(array $spec): self;

    /**
     * The SVG markup. A width class in $class (w-…, md:w-…) replaces the full-width default.
     */
    public function render(string $class = ''): string;

    /** What the drawing shows, for screen readers. */
    public function describe(): string;
}
