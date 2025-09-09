# Integracja NIP-u z automatycznym pobieraniem danych firmy

## Funkcjonalności

✅ **Automatyczne pobieranie danych firmy po wpisaniu NIP-u**
- Walidacja formatu NIP-u (suma kontrolna)
- Pobieranie danych z API Ministerstwa Finansów (biała lista VAT)
- Automatyczne wypełnienie pól: nazwa firmy, adres, status VAT
- Wizualny feedback (loader/spinner) podczas pobierania danych
- Obsługa błędów z informatywnymi komunikatami

✅ **Integracja ze Stripe**
- Wszystkie dane firmowe są przekazywane do Stripe jako metadata
- Dane są dostępne w dashboard Stripe dla każdej płatności
- Możliwość generowania faktur VAT na podstawie danych firmowych

## Jak to działa

### 1. Użytkownik wpisuje NIP
- System waliduje format NIP-u (10 cyfr + suma kontrolna)
- Po 800ms od ostatniego znaku uruchamia się pobieranie danych

### 2. Pobieranie danych z API
- Wykorzystuje API Ministerstwa Finansów: `https://wl-api.mf.gov.pl/api/search/nip/{nip}`
- Sprawdza czy firma jest aktywna VAT
- Pobiera: nazwę, adres, REGON, KRS, status VAT

### 3. Automatyczne wypełnienie formularza
- Pola firmowe pokazują się automatycznie
- Dane są tylko do odczytu (readonly)
- Wizualny feedback - zielone tło po pomyślnym pobraniu danych

### 4. Przekazanie do Stripe
- Wszystkie dane firmowe trafiają do metadata płatności
- Dostępne w Stripe Dashboard dla każdej transakcji

## Pliki zmodyfikowane/dodane

### `get-company-data.php` - NOWY
Endpoint do pobierania danych firmy:
- Walidacja NIP-u (format + suma kontrolna)
- Integracja z API Ministerstwa Finansów
- Obsługa błędów i CORS
- Zwraca dane w formacie JSON

### `index.html` - ZMODYFIKOWANY
- Dodane pola dla danych firmowych
- Nowe funkcje JavaScript do obsługi NIP-u
- Loader/spinner podczas pobierania danych
- Integracja z istniejącym kodem Stripe

### `styles.css` - ZMODYFIKOWANY
- Style dla nowych pól firmowych
- Animacja loadera/spinnera
- Style dla komunikatów błędów
- Responsywny design

### `create-intent.php` - ZMODYFIKOWANY
- Obsługa dodatkowych pól firmowych
- Przekazywanie danych firmowych do Stripe metadata

## Przykład użycia

1. **Użytkownik wpisuje NIP**: `123-456-32-18`
2. **System pobiera dane automatycznie**:
   ```json
   {
     "name": "PRZYKŁADOWA FIRMA SP. Z O.O.",
     "workingAddress": "ul. Przykładowa 123, 00-001 Warszawa",
     "vatStatus": "Aktywny VAT",
     "regon": "123456789",
     "krs": "0000123456"
   }
   ```
3. **Pola firmowe wypełniają się automatycznie**
4. **Dane trafiają do Stripe** jako metadata płatności

## API wykorzystane

### Ministerstwo Finansów - Biała lista VAT
- **URL**: `https://wl-api.mf.gov.pl/api/search/nip/{nip}?date={date}`
- **Opis**: Oficjalne API do sprawdzania statusu VAT firm
- **Bezpłatne**: Tak, bez limitów
- **Dokumentacja**: [link](https://www.gov.pl/web/kas/api-wykazu-podatnikow-vat)

## Konfiguracja

Nie wymaga dodatkowej konfiguracji - działa od razu po wgraniu plików.

## Bezpieczeństwo

- ✅ Walidacja NIP-u po stronie klienta i serwera
- ✅ Sanityzacja danych wejściowych
- ✅ Obsługa CORS
- ✅ Timeout dla requestów (10s)
- ✅ Obsługa błędów połączenia

## Testowanie

### Przykładowe NIP-y do testów:
- `123-456-32-18` - prawidłowy format (może nie istnieć w bazie)
- `525-000-88-25` - przykład firmy aktywnej VAT
- `123-456-78-90` - nieprawidłowa suma kontrolna

### Testowanie błędów:
- Nieprawidłowy format NIP-u
- Nieistniejący NIP
- Błąd połączenia z API

## Możliwe rozszerzenia

1. **Cache danych firmowych** - aby nie pobierać tych samych danych wielokrotnie
2. **Integracja z CEIDG** - dla działalności gospodarczych
3. **Weryfikacja KRS** - dodatkowa weryfikacja przez API KRS
4. **Automatyczne generowanie faktur** - na podstawie pobranych danych
5. **Eksport danych** - możliwość eksportu danych firmowych do CSV/Excel

## Wsparcie

W przypadku problemów sprawdź:
1. Czy API Ministerstwa Finansów jest dostępne
2. Czy serwer ma dostęp do Internetu (curl)
3. Logi błędów w konsoli przeglądarki
4. Logi błędów serwera PHP