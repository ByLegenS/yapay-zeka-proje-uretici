# Yapay Zeka Proje Üretici (AI Project Generator)

Bu araç, Google Gemini API'sini kullanarak, doğal dil ile yazılmış proje fikirlerini tam teşekküllü, çalışmaya hazır web projelerine dönüştüren bir PHP tabanlı bir servistir. Basit bir fikri saniyeler içinde HTML, CSS, JS ve PHP dosyalarından oluşan bir proje yapısına çevirebilirsiniz.

## 🚀 Temel Özellikler

- **Metinden Koda Çeviri:** "Blog sitesi yap" gibi basit bir komutu alıp, ilgili tüm dosyaları ve kodları üretir.
- **Fikir Geliştirme:** Tek cümlelik fikirlerinizi, "Fikrimi Geliştir ✨" özelliği ile detaylı bir teknik plana dönüştürür. Bu plan, daha tutarlı ve eksiksiz projeler üretilmesini sağlar.
- **Esnek Dosya Yapısı:** Üretilen dosyaları, API'nin önerdiği mantıksal bir klasör yapısı (`themes/`, `assets/` vb.) içinde oluşturur.
- **Detaylı Loglama:** Hata ayıklamayı kolaylaştırmak için tüm işlemleri ve olası hataları `/logs` klasöründeki gün bazlı dosyalara kaydeder.
- **Modern Arayüz:** Proje tanımlarını girmek ve sonuçları görüntülemek için Tailwind CSS ile hazırlanmış temiz ve modern bir kullanıcı arayüzüne sahiptir.

## 🛠️ Teknoloji Stack'i

- **Backend:** PHP 7.4+
- **API:** Google Gemini Pro
- **Frontend:** HTML, Tailwind CSS, JavaScript (Fetch API)

## ⚙️ Kurulum

Bu servisi kendi yerel veya uzak sunucunuzda çalıştırmak için aşağıdaki adımları izleyin:

1.  **Projeyi İndirin:**
    Proje dosyalarını sunucunuza indirin veya klonlayın.

2.  **`.env` Dosyasını Oluşturun:**
    Projenin ana dizininde `.env` adında bir dosya oluşturun ve içine Google AI Studio'dan aldığınız API anahtarınızı ekleyin.

    ```dotenv
    GOOGLE_API_KEY="BURAYA_API_ANAHTARINIZI_GIRIN"
    ```

3.  **Klasörleri Oluşturun ve İzinleri Ayarlayın:**
    Projenin ana dizininde `logs` ve `generated_projects` adında iki adet klasör oluşturun. Sunucunuzun bu klasörlere yazma izni olduğundan emin olmalısınız.

    Linux tabanlı sistemler için terminal üzerinden aşağıdaki komutları çalıştırabilirsiniz:
    ```bash
    mkdir logs
    mkdir generated_projects
    chmod -R 777 logs
    chmod -R 777 generated_projects
    ```
    > **⚠️ Güvenlik Uyarısı:** `777` izni, herkese tam yetki verir ve güvenlik açısından riskli olabilir. Canlı sunucularda, sunucu kullanıcısına (`www-data` gibi) özel yazma izni vermek (`chown`, `chmod 755`) daha güvenli bir yöntemdir.

## 💡 Kullanım

1.  **Arayüzü Açın:**
    Tarayıcınızdan projenin bulunduğu dizindeki `index.php` dosyasını açın.
    (Örn: `http://localhost/ai-project-generator/index.php`)

2.  **Fikrinizi Girin:**
    "Proje Tanımı" alanına oluşturmak istediğiniz projenin tanımını girin.

3.  **(İsteğe Bağlı) Fikrinizi Geliştirin:**
    Eğer fikriniz çok basitse (örn: "E-ticaret sitesi"), önce **"Fikrimi Geliştir ✨"** butonuna tıklayın. Yapay zeka, bu fikri daha detaylı bir teknik plana dönüştürerek metin kutusunu güncelleyecektir.

4.  **Projeyi Oluşturun:**
    Hazır olduğunuzda **"Projeyi Oluştur"** butonuna tıklayın. İşlem, projenin karmaşıklığına göre biraz zaman alabilir.

5.  **Sonucu Kontrol Edin:**
    İşlem tamamlandığında, `generated_projects` klasörünün içinde projenizin benzersiz bir ID ile oluşturulmuş yeni klasörünü ve içindeki dosyaları bulabilirsiniz.

## 📜 Lisans

Bu proje GNU Genel Kamu Lisansı (GPLv3) altında lisanslanmıştır.

🔔 KANALIMA ABONE OL:
https://www.youtube.com/@YucelKahramanYT?sub_confirmation=1