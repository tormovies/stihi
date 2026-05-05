{{-- Подвал сайта. Менять в одном месте. --}}
    <footer class="site-footer">
        <div class="container">
            <p>© {{ date('Y') }} Стихотворения поэтов классиков</p>
            <p class="site-footer-links">
                <a href="{{ route('legal.privacy') }}">Политика обработки персональных данных</a>
                <span>·</span>
                <a href="{{ route('legal.cookies') }}">Политика cookie</a>
                <span>·</span>
                <button type="button" class="site-footer-cookie-btn" id="open-cookie-settings">Настройки cookie</button>
            </p>
        </div>
    </footer>
    <div class="cookie-consent-backdrop" id="cookie-consent-backdrop" hidden></div>
    <section class="cookie-consent" id="cookie-consent" role="dialog" aria-live="polite" aria-label="Настройки cookie" hidden>
        <p class="cookie-consent-title">Файлы cookie на сайте</p>
        <p class="cookie-consent-text">
            Мы используем обязательные cookie для работы сайта и аналитические cookie (Яндекс.Метрика) только с Вашего согласия.
            Подробнее в <a href="{{ route('legal.cookies') }}">Политике cookie</a>.
        </p>
        <div class="cookie-consent-actions">
            <button type="button" class="cookie-btn cookie-btn-secondary" id="cookie-reject-analytics">Только обязательные</button>
            <button type="button" class="cookie-btn cookie-btn-primary" id="cookie-accept-all">Принять все</button>
        </div>
    </section>
