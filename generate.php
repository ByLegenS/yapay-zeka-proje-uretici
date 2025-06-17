<?php

// Hata raporlamayı açarak geliştirme sırasında sorunları daha kolay görebiliriz.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tarayıcıya yanıtın JSON formatında olacağını belirtiyoruz.
header('Content-Type: application/json');

// Gerekli servis sınıflarını dahil et.
require_once __DIR__ . '/services/LoggerService.php';
require_once __DIR__ . '/services/ConfigManager.php';
require_once __DIR__ . '/services/GeminiApiClient.php';
require_once __DIR__ . '/services/ProjectGeneratorService.php';

// Log servisini başlat
$logger = new LoggerService(__DIR__ . '/logs');

try {
    // .env dosyasını yükle.
    ConfigManager::load(__DIR__);
    $apiKey = ConfigManager::get('GOOGLE_API_KEY');
    if (!$apiKey) {
        throw new \Exception("GOOGLE_API_KEY anahtarı .env dosyasında bulunamadı.");
    }

    // İstek gövdesinden JSON verisini al.
    $requestBody = file_get_contents('php://input');
    $requestData = json_decode($requestBody, true);
    
    $userPrompt = $requestData['prompt'] ?? null;
    if (empty($userPrompt)) {
        throw new \Exception("Proje tanımı (prompt) boş olamaz.");
    }
    
    // Servisleri başlat.
    $apiClient = new GeminiApiClient($apiKey, $logger);
    $generatorService = new ProjectGeneratorService($apiClient, $logger);
    
    // Projeyi oluştur.
    $projectPath = $generatorService->generateProject($userPrompt);

    // Başarı mesajını döndür.
    echo json_encode([
        'success' => true,
        'message' => "Proje başarıyla oluşturuldu!\nSunucudaki Yolu: " . $projectPath
    ]);

} catch (\Exception $e) {
    // Hata durumunda, hatayı logla ve ardından yanıtı döndür.
    $logger->log('generate.php içinde bir hata oluştu: ' . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}