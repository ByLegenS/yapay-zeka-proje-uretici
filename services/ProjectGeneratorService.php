<?php

/**
 * Kullanıcı prompt'una göre Gemini API'sini kullanarak proje dosyaları üreten servis.
 */
class ProjectGeneratorService
{
    /**
     * @var GeminiApiClient API istemcisi nesnesi.
     */
    private $apiClient;
    
    /**
     * @var LoggerService Loglama servisi nesnesi.
     */
    private $logger;

    /**
     * ProjectGeneratorService constructor.
     *
     * @param GeminiApiClient $apiClient API istemcisi.
     * @param LoggerService   $logger    Loglama servisi.
     */
    public function __construct(GeminiApiClient $apiClient, LoggerService $logger)
    {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
    }

    /**
     * Verilen prompt'a göre tam bir proje oluşturur ve dosyaları fiziksel olarak yazar.
     *
     * @param string $userPrompt Kullanıcının proje tanımı.
     * @return string Oluşturulan projenin sunucudaki yolu.
     * @throws \Exception API'den geçersiz yanıt gelirse veya dosya yazma hatası olursa.
     */
    public function generateProject(string $userPrompt): string
    {
        $this->logger->log("Proje üretme işlemi başlatılıyor.", 'INFO');
        $masterPrompt = $this->buildMasterPrompt($userPrompt);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $masterPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'temperature' => 0.3
            ]
        ];

        $this->logger->log("API'ye istek gönderiliyor.", 'INFO');
        $apiResponse = $this->apiClient->sendRequest($payload);

        $jsonString = $apiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (empty($jsonString)) {
            $this->logger->log("API'den boş veya beklenen formatta olmayan bir yanıt alındı.", 'ERROR');
            throw new \Exception("API'den beklenen içerik alınamadı veya yanıt boş.");
        }
        
        $this->logger->log("API'den yanıt alındı. JSON çözümleniyor.", 'INFO');

        $jsonString = trim($jsonString);
        if (strpos($jsonString, '```json') === 0) {
            $this->logger->log("Yanıtın başında '```json' bloğu tespit edildi, temizleniyor.", 'WARNING');
            $jsonString = substr($jsonString, 7);
            if (substr($jsonString, -3) === '```') {
                $jsonString = substr($jsonString, 0, -3);
            }
        }
        $jsonString = trim($jsonString);

        $projectData = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // HATA ANINDA HAM YANITI LOGLA
            $this->logger->log(
                "JSON çözümleme hatası: " . json_last_error_msg() . ". Alınan Ham Yanıt: " . $jsonString,
                'ERROR'
            );
            throw new \Exception("API'den dönen metin geçerli bir JSON değil: " . json_last_error_msg());
        }
        
        $this->logger->log("JSON başarıyla çözümlendi. Dosyalar oluşturuluyor.", 'INFO');
        return $this->createFilesFromJson($projectData);
    }

    /**
     * API'den gelen JSON verisine göre proje dosyalarını ve dizinlerini oluşturur.
     *
     * @param array $data API'den gelen, dosya yapısını içeren dizi.
     * @return string Oluşturulan projenin kök dizininin yolu.
     * @throws \Exception Dosya veya dizin oluşturma hatası durumunda.
     */
    private function createFilesFromJson(array $data): string
    {
        $projectRoot = dirname(__DIR__) . '/generated_projects/' . uniqid('proje_');
        if (!is_dir($projectRoot) && !mkdir($projectRoot, 0777, true)) {
             $this->logger->log("Ana proje dizini oluşturulamadı: {$projectRoot}", 'ERROR');
             throw new \Exception("Ana proje dizini oluşturulamadı: {$projectRoot}");
        }

        if (!isset($data['files']) || !is_array($data['files'])) {
            $this->logger->log("JSON yanıtta beklenen 'files' anahtarı bulunamadı.", 'ERROR');
            throw new \Exception("JSON yanıtta beklenen 'files' anahtarı bulunamadı.");
        }

        $totalFiles = 0;
        foreach ($data['files'] as $fileType => $files) {
            if (!is_array($files)) continue;

            foreach ($files as $file) {
                if (!isset($file['name']) || !isset($file['source'])) continue;

                $filePath = $projectRoot . '/' . ltrim($file['name'], '/');
                $fileDir = dirname($filePath);

                if (!is_dir($fileDir)) {
                    if (!mkdir($fileDir, 0777, true)) {
                        $this->logger->log("Alt dizin oluşturulamadı: {$fileDir}", 'ERROR');
                        throw new \Exception("Alt dizin oluşturulamadı: {$fileDir}");
                    }
                }

                if (file_put_contents($filePath, $file['source']) === false) {
                    $this->logger->log("Dosya yazılamadı: {$filePath}", 'ERROR');
                    throw new \Exception("Dosya yazılamadı: {$filePath}");
                }
                $totalFiles++;
            }
        }
        
        $this->logger->log("{$totalFiles} adet dosya başarıyla oluşturuldu. Proje yolu: {$projectRoot}", 'INFO');
        return $projectRoot;
    }


    /**
     * Kullanıcı isteğini, Gemini'ye gönderilecek ana komut şablonuna yerleştirir.
     *
     * @param string $userPrompt Kullanıcının girdiği ham proje tanımı.
     * @return string Gemini API'sine gönderilmeye hazır tam komut.
     */
    private function buildMasterPrompt(string $userPrompt): string
    {
        // Not: Kullanıcının eklediği çift kural korunmuştur.
        return <<<PROMPT
        SENARYO:
        Sen, modern web standartlarına hakim, tecrübeli bir Full-Stack Web Geliştiricisisin. Görevin, aşağıda kullanıcının belirttiği isteklere göre sıfırdan, eksiksiz bir web projesi oluşturmaktır. Projedeki tüm dosyaların (PHP, HTML, CSS, JS) birbiriyle uyumlu, tutarlı ve çalışır durumda olması kritik öneme sahiptir.

        TEMEL KURALLAR:
        1.  SADECE JSON ÇIKTISI: Cevabın, BAŞKA HİÇBİR AÇIKLAMA VEYA METİN OLMADAN, sadece ve sadece geçerli bir JSON nesnesi olmalıdır.
        2.  SADECE VE SADECE, BAŞKA HIÇBIR METIN, AÇIKLAMA VEYA MARKDOWN FORMATI (` ```json` gibi) OLMADAN, saf bir JSON nesnesi döndür.
        3.  JSON ŞEMASINA UYUM: Üreteceğin JSON, aşağıdaki şemaya harfiyen uymalıdır:
            {
            "files": {
                "html": [ {"name": "dosya/yolu/dosya.html", "source": "kod..."} ],
                "css": [ {"name": "dosya/yolu/dosya.css", "source": "kod..."} ],
                "js": [ {"name": "dosya/yolu/dosya.js", "source": "kod..."} ],
                "php": [ {"name": "dosya/yolu/dosya.php", "source": "kod..."} ]
            }
            }
        3.  DOSYA YOLLARI: Dosya yollarını mantıksal bir dizin yapısına göre oluştur. (Örn: `themes/default/header.php`, `assets/css/style.css`).
        4.  KOD KALİTESİ:
            - Ürettiğin kodlar temiz, okunabilir ve modern standartlara uygun olsun.
            - HTML dosyalarındaki CSS ve JS dosyası yollarının (`<link>`, `<script>`) ürettiğin dosya yapısıyla eşleştiğinden emin ol.
            - PHP dosyalarındaki `include` veya `require` ifadelerinin, yine ürettiğin dosya yapısıyla tutarlı olduğundan emin ol.
            - Güvenlik temellerine dikkat et (Örn: Form verilerini işlerken temel düzeyde güvenlik sağla).
            - Satır aralarına yorum ekleyerek kodun ne işe yaradığını kısaca belirt.

        KULLANICININ İSTEĞİ:
        ---
        {$userPrompt}
        ---
        PROMPT;
    }
}
