# JWT Auth API (PHP + Docker)

Bu proje, PHP ile JWT tabanlı kullanıcı kimlik doğrulama işlemlerini gerçekleştiren bir RESTful API servisidir. Docker, PostgreSQL ve Redis entegrasyonu ile birlikte gelir. 

## ✨ Özellikler

- Kullanıcı kayıt, giriş, şifre sıfırlama
- JWT (access token) ile kimlik doğrulama
- Mail gönderimi (SMTP / AWS) – Factory Pattern
- PSR-4 autoloading (Composer)
- MVC yapısı
- Docker ile çalıştırılabilir yapı

---

## 📁 Proje Yapısı
```
app/
├── Controllers/ # HTTP işlemleri
├── Middleware/  # JWT, RateLimit gibi kontroller
├── Models/      # DB işlemleri (User, PasswordReset)
├── Services/
│ ├── Mail/      # SMTP, AWS MailService + Factory
│ └── UserService.php # İş mantığı burada
├── Routes/      # web.php (endpoint tanımları)
├── helpers/     # Yardımcı fonksiyonlar
config/
├── database.php # PDO bağlantısı
docker/
├── php/         # Dockerfile
├── nginx/       # Nginx config
.env             # Ortam değişkenleri
docker-compose.yml
```

---

## 🚀 Kurulum

### 1. Repo'yu Klonla

```bash
git clone https://github.com/kullaniciadi/jwt-auth-api.git
cd jwt-auth-api
```

### 2. .env Dosyasını Oluştur
```
MAIL_DRIVER = 'smtp'
MAIL_HOST = 'smtp.gmail.com'
MAIL_PORT = 587
MAIL_USERNAME = 'example@gmail.com'
MAIL_PASSWORD = 'apppassword'
MAIL_ENCRYPTION = 'tls'
```

### 3. Dockor Compose Başlat
```bash
docker-compose up --build
```

### 4. PostgreSQL'e Bağlan ve Tabloları Oluştur
PostgreSQL konteynerine bağlandıktan sonra, aşağıdaki SQL sorgularını çalıştırarak gerekli tabloları oluşturabilirsiniz.
```bash
docker exec -it postgres_db psql -U user -d auth_db
```

```sql
CREATE TABLE users (
  id SERIAL PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  name VARCHAR(100),
  surname VARCHAR(100),
  password TEXT NOT NULL,
  tc VARCHAR(11),
  email VARCHAR(255),
  verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE password_reset_codes (
  id SERIAL PRIMARY KEY,
  user_id INT REFERENCES users(id),
  code VARCHAR(6),
  status BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP
);
```

## 📦 Katkı
Katkı yapmak isterseniz PR gönderebilir veya issue açabilirsiniz.
