<footer class="on-night border-t border-gold/70 bg-night text-on-night">
    <div class="page py-14 lg:py-20">
        <div class="grid gap-12 md:grid-cols-[1.6fr_1fr]">
            <div>
                <a href="{{ route('home') }}" class="inline-block rounded-sm" aria-label="Stiqon – zur Startseite">
                    <x-site.logo variant="light" class="h-10 lg:h-12" sizes="(min-width: 64rem) 156px, 130px" loading="lazy" />
                </a>
                <p class="mt-6 max-w-sm text-on-night-muted">
                    Sticker und Flyer mit Ihrem Motiv – erst ansehen, dann bestellen.
                </p>
            </div>

            <nav aria-labelledby="footer-areas">
                <h2 id="footer-areas" class="text-overline text-on-night-muted">Bereiche</h2>
                <ul class="mt-4 space-y-1">
                    <li><a href="{{ route('area.show', 'sticker') }}" class="inline-flex h-10 items-center rounded-sm hover:underline">Sticker</a></li>
                    <li><a href="{{ route('area.show', 'flyer') }}" class="inline-flex h-10 items-center rounded-sm hover:underline">Flyer</a></li>
                </ul>
            </nav>
        </div>

        <div class="mt-14 flex flex-col gap-2 border-t border-on-night/15 pt-6 text-sm text-on-night-muted sm:flex-row sm:justify-between">
            <p>© {{ now()->year }} Stiqon</p>
            <p>Vorschau – der Shop ist noch im Aufbau.</p>
        </div>
    </div>
</footer>
