<?php

// Hata raporlamayı aç
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Yanıt tipini JSON olarak ayarla
header('Content-Type: application/json');

// Gerekli sınıfları dahil et
require_once __DIR__ . '/services/LoggerService.php';
require_once __DIR__ . '/services/ConfigManager.php';
require_once __DIR__ . '/services/GeminiApiClient.php';

// Log servisini başlat
$logger = new LoggerService(__DIR__ . '/logs');

try {
    // .env dosyasını ve API anahtarını yükle
    ConfigManager::load(__DIR__);
    $apiKey = ConfigManager::get('GOOGLE_API_KEY');
    if (!$apiKey) {
        throw new \Exception("GOOGLE_API_KEY anahtarı .env dosyasında bulunamadı.");
    }

    // İstek gövdesinden kullanıcı fikrini al
    $requestBody = file_get_contents('php://input');
    $requestData = json_decode($requestBody, true);
    $userPrompt = $requestData['prompt'] ?? null;
    if (empty($userPrompt)) {
        throw new \Exception("Geliştirilecek fikir (prompt) boş olamaz.");
    }
    
    $logger->log("Fikir geliştirme işlemi başlatılıyor.", "INFO");

    // Gemini API istemcisini başlat
    $apiClient = new GeminiApiClient($apiKey, $logger);

    // Fikir geliştirme için özel olarak hazırlanmış ana komutu oluştur
    $enhancerPrompt = buildEnhancerPrompt($userPrompt);

    // API'ye gönderilecek payload'ı hazırla
    $payload = [
        'contents' => [['parts' => [['text' => $enhancerPrompt]]]],
        'generationConfig' => [
            'temperature' => 0.5,
            'response_mime_type' => 'text/plain',
        ]
    ];
    
    // API'ye isteği gönder
    $apiResponse = $apiClient->sendRequest($payload);
    
    $enhancedText = $apiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (empty($enhancedText)) {
        $logger->log("API'den fikir geliştirme için boş yanıt alındı.", "ERROR");
        throw new \Exception("API'den fikir geliştirme için bir yanıt alınamadı.");
    }

    $logger->log("Fikir başarıyla geliştirildi.", "INFO");
    // Başarılı yanıtı döndür
    echo json_encode([
        'success' => true,
        'enhanced_prompt' => trim($enhancedText)
    ]);

} catch (\Exception $e) {
    // Hata durumunda, hatayı logla ve ardından yanıtı döndür
    $logger->log('enhance.php içinde bir hata oluştu: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Kullanıcı fikrini, Gemini'ye gönderilecek "Fikir Geliştirici" komut şablonuna yerleştirir.
 *
 * @param string $userPrompt Kullanıcının girdiği ham proje fikri.
 * @return string Gemini API'sine gönderilmeye hazır tam komut.
 */
function buildEnhancerPrompt(string $userPrompt): string
{
    return <<<PROMPT
SENARYO:
Sen, bir yazılım projesi fikrini alıp onu detaylı bir teknik ve işlevsel plana dönüştüren, tecrübeli bir Proje Yöneticisi ve Baş Geliştiricisin. Görevin, aşağıda kullanıcının sunduğu basit fikri zenginleştirmek ve tam teşekküllü bir proje tanımı oluşturmaktır.

KURALLAR:
1. KULLANICININ ANA FİKRİNE SADIK KAL: Fikri tamamen değiştirme, sadece geliştir ve detaylandır.
2. TEKNOLOJİ TAVSİYELERİNDE BULUN: Projenin gereksinimlerine uygun teknolojiler öner (Örn: "Dinamik bir arayüz için React veya Vue.js", "Hızlı ve modern bir tasarım için Tailwind CSS", "Sunucu tarafı mantığı için PHP veya Node.js").
3. ÖZELLİKLERİ DETAYLANDIR: Kullanıcının istediği sayfaları (ana sayfa, hakkımda vb.) ve bu sayfalarda olması gereken bileşenleri (galeri, form, menü vb.) net bir şekilde listele.
4. DOSYA YAPISI ÖNER: Kodun organize olacağı mantıksal bir dosya yapısı önerisi ekle.
5. ÇIKTI FORMATI: Cevabın, sadece ve sadece bu zenginleştirilmiş proje tanımını içeren, başka hiçbir ek açıklama veya giriş/çıkış metni olmayan, düz bir metin olmalıdır. Bu metin, doğrudan başka bir yapay zeka modeline "proje oluşturma" komutu olarak verilebilecek kadar net ve detaylı olmalıdır.

KULLANICININ BASİT FİKRİ:
---
{$userPrompt}
---
PROMPT;
}
