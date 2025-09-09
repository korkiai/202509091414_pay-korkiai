# 🚀 Instrukcje wdrożenia koszyka Stripe na cPanel

## 📁 Pliki do wgrania na serwer

Wgraj cały folder `pay/` do katalogu głównego domeny `www.pay.korkiai.pl`:

```
pay/
├── index.html              # Strona koszyka
├── thank-you.html          # Strona podziękowania
├── styles.css              # Style koszyka
├── config.php              # Endpoint konfiguracji
├── create-intent.php       # Endpoint tworzenia płatności
├── get-company-data.php    # Endpoint pobierania danych firmowych z NIP
├── platnosc.jpg            # Zdjęcie produktu
├── .htaccess.example       # Przykład konfiguracji Apache
└── .well-known/            # Folder weryfikacji Apple Pay
    └── apple-developer-merchantid-domain-association
```

## 🔐 Konfiguracja kluczy Stripe w cPanel

### Opcja 1: Zmienne środowiskowe (zalecane)

1. Zaloguj się do cPanel
2. Znajdź sekcję "Software" → "Environment Variables"
3. Dodaj zmienne:
   - `STRIPE_PUBLISHABLE_KEY` = `pk_test_YOUR_PUBLISHABLE_KEY_HERE`
   - `STRIPE_SECRET_KEY` = `sk_test_YOUR_SECRET_KEY_HERE`

### Opcja 2: Plik .htaccess

1. Skopiuj `.htaccess.example` do `.htaccess`
2. Edytuj plik i wstaw swoje klucze:
```apache
SetEnv STRIPE_PUBLISHABLE_KEY pk_test_YOUR_PUBLISHABLE_KEY_HERE
SetEnv STRIPE_SECRET_KEY sk_test_YOUR_SECRET_KEY_HERE
```

## 💳 Konfiguracja metod płatności w Stripe Dashboard

### 1. Włączenie podstawowych metod

1. Zaloguj się do [Stripe Dashboard](https://dashboard.stripe.com)
2. Przejdź do **Settings** → **Payment methods**
3. Włącz metody (widzę że już masz włączone):
   - ✅ **Cards** - już aktywne
   - ✅ **BLIK** - już aktywne (Polska)
   - ✅ **Przelewy24** - już aktywne (Polska)

### 2. Konfiguracja Google Pay

1. W Stripe Dashboard: **Settings** → **Payment methods**
2. Znajdź **Google Pay** i kliknij **Enable**
3. Dodaj domenę: `www.pay.korkiai.pl`
4. Google Pay pojawi się automatycznie na urządzeniach Android z Chrome

### 3. Konfiguracja Apple Pay

1. W Stripe Dashboard: **Settings** → **Payment methods**
2. Znajdź **Apple Pay** i kliknij **Enable**
3. Dodaj domenę: `www.pay.korkiai.pl`
4. **Weryfikacja domeny:**
   - Wgraj plik `.well-known/apple-developer-merchantid-domain-association` (już jest w folderze)
   - Umieść go w głównym katalogu strony: `/.well-known/apple-developer-merchantid-domain-association`
   - Kliknij "Verify" w Stripe Dashboard

## 🧪 Testowanie

### Lokalne testowanie
```bash
# Uruchom lokalny serwer PHP (opcjonalne)
php -S localhost:8000
```

### Testowanie na serwerze
1. Otwórz: `https://www.pay.korkiai.pl/index.html`
2. Wybierz wariant kursu
3. Wypełnij dane i wybierz metodę płatności
4. Użyj testowych numerów kart:
   - **Visa**: `4242 4242 4242 4242`
   - **Mastercard**: `5555 5555 5555 4444`
   - **BLIK**: użyj kodu `123456`

### Testowanie metod płatności

#### 💳 Karty (zawsze dostępne)
- **Visa**: `4242 4242 4242 4242`
- **Mastercard**: `5555 5555 5555 4444`
- **CVC**: dowolny 3-cyfrowy kod (np. `123`)
- **Data**: dowolna przyszła data

#### 📱 BLIK (tylko Polska)
- Kod BLIK: `123456`
- Po wprowadzeniu kodu pojawi się symulacja autoryzacji
- W trybie testowym zawsze się powiedzie

#### 🏦 Przelewy24 (tylko Polska)
- Wybierz dowolny bank z listy
- Zostaniesz przekierowany na stronę testową P24
- Kliknij "Authorize payment" aby zatwierdzić

#### 📱 Google Pay (automatyczne wykrywanie)
- Pojawi się tylko na Chrome/Android z skonfigurowanym Google Pay
- W trybie testowym użyj testowej karty Google Pay
- Kliknij przycisk Google Pay i autoryzuj

#### 🍎 Apple Pay (automatyczne wykrywanie)
- Pojawi się tylko na Safari/iOS/macOS z Touch ID/Face ID
- W trybie testowym użyj testowej karty Apple Pay
- Autoryzuj przez Touch ID/Face ID

## 📊 Monitorowanie płatności

1. **Stripe Dashboard** → **Payments** - wszystkie transakcje
2. **Logs** - logi błędów
3. **Webhooks** - powiadomienia o statusie płatności (opcjonalne)

## 🔄 Przejście na produkcję

Gdy wszystko działa poprawnie:

1. W Stripe Dashboard przełącz z "Test mode" na "Live mode"
2. Skopiuj **live keys** (zaczynają się od `pk_live_` i `sk_live_`)
3. Zaktualizuj zmienne środowiskowe w cPanel:
   - `STRIPE_PUBLISHABLE_KEY` = `pk_live_...`
   - `STRIPE_SECRET_KEY` = `sk_live_...`
4. Przetestuj jedną transakcję z prawdziwą kartą

## 🛡️ Bezpieczeństwo

- ✅ Klucze nie są hardcodowane w kodzie
- ✅ Secret key tylko po stronie serwera
- ✅ Wszystkie płatności przez HTTPS
- ✅ Walidacja kwot po stronie serwera
- ✅ Automatyczne wykrywanie metod płatności
- ✅ Automatyczne pobieranie danych firmowych z NIP

## 🏢 Funkcja automatycznego pobierania danych firmowych

Koszyk automatycznie pobiera dane firmy na podstawie wpisanego NIP-u:

### Jak działa:
1. Użytkownik wpisuje NIP w formacie `123-456-32-18`
2. System waliduje format i sumę kontrolną NIP-u
3. Po 800ms opóźnienia wysyła zapytanie do API GUS (Główny Urząd Statystyczny)
4. Automatycznie wypełnia pola: nazwa firmy, adres, status VAT
5. Dane są zapisywane w metadata płatności w Stripe

### Obsługiwane formaty NIP:
- `1234567890` (tylko cyfry)
- `123-456-78-90` (z myślnikami)
- `123 456 78 90` (ze spacjami)

### API używane:
- **Produkcja**: `https://wl-api.mf.gov.pl/api/search/nip/` (darmowe API Ministerstwa Finansów)
- **Fallback**: Jeśli API nie odpowiada, wyświetla podstawowe informacje

## 📞 Wsparcie

Jeśli coś nie działa:
1. Sprawdź logi PHP w cPanel
2. Sprawdź Network tab w przeglądarce
3. Sprawdź Stripe Dashboard → Logs
4. Skontaktuj się z pomocą techniczną hostingu

---
✨ **Gotowe!** Koszyk płatności jest już dostępny pod: `https://www.pay.korkiai.pl/`