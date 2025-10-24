# 🚌 Bilet Satın Alma Sistemi

Modern ve güvenli bir **otobüs bileti satın alma platformu**. PHP 8.2, SQLite ve Docker ile geliştirilmiştir.

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://www.php.net/)
[![SQLite](https://img.shields.io/badge/SQLite-3-green.svg)](https://www.sqlite.org/)
[![Docker](https://img.shields.io/badge/Docker-Ready-brightgreen.svg)](https://www.docker.com/)

---

## 📋 İçindekiler

- [Proje Özeti](#-proje-özeti)
- [Hızlı Başlangıç](#-hızlı-başlangıç)
- [Test Hesapları](#-test-hesapları)
- [Özellikler](#-özellikler)
- [Güvenlik](#-güvenlik)
- [Gereksinimler Kontrol Listesi](#-gereksinimler-kontrol-listesi)

---

## 🎯 Proje Özeti

Bu sistem, **otobüs firmaları** için çevrimiçi bilet satış ve yönetim platformudur. Üç farklı kullanıcı rolü (Admin, Firma Admin, Yolcu) ile çalışır.

### Temel Özellikler

- 🎫 **Bilet Satın Alma**: Kredi sistemi ile güvenli ödeme
- 🔄 **Bilet İptal**: Seferden 1 saat öncesine kadar (ücret iadeli)
- 🎟️ **Kupon Sistemi**: Global ve firma-özel indirim kuponları
- 📄 **PDF Bilet**: Her bilet PDF formatında indirilebilir
- 🚌 **Sefer Yönetimi**: Firma yetkilileri kendi seferlerini yönetir
- 👥 **Kullanıcı Yönetimi**: Admin paneli ile tam kontrol

---


### Kurulum (2 Adım)

#### 1. Projeyi İndirin

```bash
git clone <repo-url>
cd ticketproject
```

#### 2. Docker ile Başlatın

```bash
# Docker container'ı başlat
docker-compose -f docker/docker-compose.yml up -d
```

## 👥 Test Hesapları

### 🔐 Admin Paneli

**Yetki**: Tüm sistem yönetimi (firmalar, kullanıcılar, global kuponlar)

| Kullanıcı Adı | Şifre     |   |
|---------------|-----------|---------------------------|
| `admin`       | `admin123`|  |

**Admin Özellikleri**:
- ✅ Firma ekle/düzenle/sil
- ✅ Firma yöneticileri oluştur/düzenle
- ✅ Global kupon yönetimi
- ✅ Tüm kullanıcıları görüntüle

---

### 🏢 Firma Admin Panelleri

**Yetki**: Kendi firmasının seferleri ve firma-özel kuponları

#### Metro Turizm
| Kullanıcı Adı     | Şifre      | Erişim URL                       |
|-------------------|------------|----------------------------------|
| `metro_admin`     | `metro123` | http://localhost:8080/firm-admin/trips |

#### Pamukkale Turizm
| Kullanıcı Adı         | Şifre          | Erişim URL                       |
|-----------------------|----------------|----------------------------------|
| `pamukkale_admin`     | `pamukkale123` | http://localhost:8080/firm-admin/trips |

#### Kamil Koç
| Kullanıcı Adı       | Şifre         | Erişim URL                       |
|---------------------|---------------|----------------------------------|
| `kamilkoc_admin`    | `kamilkoc123` | http://localhost:8080/firm-admin/trips |

**Firma Admin Özellikleri**:
- ✅ Sefer ekle/düzenle/sil (kendi firması için)
- ✅ Firma-özel kupon oluştur
- ✅ Sadece kendi firmalarının verilerini görür

---

### 🧑‍💼 Yolcu (Normal Kullanıcı)

**Yetki**: Bilet satın alma, görüntüleme, iptal etme

| Kullanıcı Adı | Şifre      | Bakiye    | Erişim URL                    |
|---------------|------------|-----------|-------------------------------|
| `yolcu1`      | `yolcu123` | 5000 TL   | http://localhost:8080/me/tickets |
| `yolcu2`      | `yolcu123` | 3000 TL   | http://localhost:8080/me/tickets |

**Yolcu Özellikleri**:
- ✅ Sefer arama ve listeleme
- ✅ Koltuk seçimi ve bilet satın alma
- ✅ Kupon kodu ile indirim
- ✅ Biletlerimi görüntüle
- ✅ PDF bilet indirme
- ✅ Bilet iptal (1 saat kuralı)

---

### 🎟️ Örnek Kupon Kodları

Bilet satın alırken kullanabilirsiniz:

#### Global Kuponlar (Tüm Firmalarda Geçerli)
| Kod          | İndirim | Kullanım Limiti | Son Kullanma |
|--------------|---------|-----------------|--------------|
| `WELCOME20`  | %20     | 100 kişi        | +90 gün      |
| `NEWYEAR25`  | %25     | 50 kişi         | +60 gün      |
| `SUMMER15`   | %15     | 200 kişi        | +30 gün      |

#### Firma-Özel Kuponlar
| Kod            | Firma            | İndirim | Kullanım Limiti |
|----------------|------------------|---------|-----------------|
| `METRO10`      | Metro Turizm     | %10     | 50 kişi         |
| `PAMUKKALE15`  | Pamukkale Turizm | %15     | 30 kişi         |

## 🔒 Güvenlik

### Uygulanan Güvenlik Önlemleri

#### 1. SQL Injection Koruması
- ✅ PDO Prepared Statements (100% coverage)
- ✅ Hiçbir yerde raw SQL concatenation yok
- ✅ `PDO::ATTR_EMULATE_PREPARES = false`

#### 2. XSS (Cross-Site Scripting) Koruması
- ✅ Tüm çıktılar `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`
- ✅ PDF içeriği `strip_tags` + `preg_replace` ile temizlenir
- ✅ Content-Security-Policy header

#### 3. CSRF (Cross-Site Request Forgery) Koruması
- ✅ Tüm POST formlarında CSRF token (`Csrf::validateOrFail()`)
- ✅ Token session'da saklanır
- ✅ Her kullanıcı için unique token

#### 4. RBAC (Role-Based Access Control)
- ✅ `Auth::requireRole('admin')` kontrolü
- ✅ Yetkisiz erişimde 403 + flash message
- ✅ Rol bazlı menü ve buton görünürlüğü

#### 5. IDOR (Insecure Direct Object Reference) Koruması
- ✅ Ticket: `ticket->user_id === Auth::id()`
- ✅ Trip: `trip->firma_id === Auth::firmaId()`
- ✅ Coupon: `coupon->firma_id === Auth::firmaId()`

#### 6. Brute-Force Koruması
- ✅ 5 başarısız denemeden sonra progressive delay (1-5 saniye)
- ✅ 15 dakika hesap kilitleme (opsiyonel)
- ✅ Failed attempts session'da takip edilir

#### 7. Session Güvenliği
- ✅ `session.cookie_httponly = 1` (XSS koruması)
- ✅ `session.cookie_samesite = Strict` (CSRF koruması)
- ✅ `session.cookie_secure = 1` (production HTTPS)
- ✅ `session_regenerate_id(true)` on login/register

#### 8. HTTP Security Headers
- ✅ `Content-Security-Policy`: XSS koruması
- ✅ `X-Frame-Options: DENY`: Clickjacking koruması
- ✅ `X-Content-Type-Options: nosniff`: MIME sniffing koruması
- ✅ `Strict-Transport-Security` (production HTTPS)
- ✅ `Referrer-Policy: no-referrer`
- ✅ `Permissions-Policy`: Gereksiz API'ler devre dışı

#### 9. Transaction Güvenliği
- ✅ PDO transactions (`BEGIN`, `COMMIT`, `ROLLBACK`)
- ✅ Atomic operations (kredi düşme + bilet oluşturma)
- ✅ Error handling ile automatic rollback
- ✅ WAL (Write-Ahead Logging) mode for concurrency

#### 10. Password Güvenliği
- ✅ `password_hash(PASSWORD_DEFAULT)` (Bcrypt)
- ✅ Minimum 8 karakter
- ✅ `password_verify()` for authentication

#### 11. Input Validation
- ✅ Server-side validation (client-side yeterli değil)
- ✅ Integer boundaries check
- ✅ String length limits
- ✅ Date/time format validation
- ✅ Whitelist validation (roles, status)

#### 12. Race Condition Koruması
- ✅ SQLite trigger: Aynı koltuk iki kez satılamaz
- ✅ Server-side check: `SELECT COUNT(*) WHERE seat AND status='active'`
- ✅ Atomic coupon decrement: `UPDATE ... WHERE usage_limit > 0`



## 📝 Notlar

### Kredi Sistemi

- Yeni kayıt olan kullanıcılara otomatik **0 TL** kredi verilir
- Admin panelinden kullanıcı kredisi artırılabilir
- Bilet satın alımda kredi düşer
- Bilet iptalinde kredi iade edilir

### Kupon Sistemi

- **Global kuponlar**: Tüm firmalarda geçerlidir (`firma_id = NULL`)
- **Firma-özel kuponlar**: Sadece belirli firmada geçerlidir (`firma_id = X`)
- Kuponlar kullanım limitine sahiptir (`usage_limit`)
- Süresi dolan kuponlar otomatik olarak geçersiz olur




