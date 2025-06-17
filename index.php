<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Proje Üretici</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        #result-container pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen">

    <div class="w-full max-w-4xl mx-auto p-4 md:p-8">
        <div class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-2 bg-clip-text text-transparent bg-gradient-to-r from-purple-400 to-indigo-600">
                Yapay Zeka Proje Üretici
            </h1>
            <p class="text-gray-400">Proje fikrinizi doğal bir dille anlatın, kod ve dosyalar anında hazırlansın.</p>
        </div>

        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-2xl">
            <div class="mb-4">
                <label for="prompt-input" class="block text-sm font-medium text-gray-300 mb-2">Proje Tanımı:</label>
                <textarea id="prompt-input" rows="10" class="w-full bg-gray-900 border border-gray-600 rounded-md p-3 text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" placeholder="Örn: Bir blog sitesi."></textarea>
            </div>

            <div class="flex flex-col sm:flex-row gap-4">
                <button id="enhance-btn" class="w-full sm:w-auto bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-md transition duration-300 flex items-center justify-center">
                    Fikrimi Geliştir ✨
                </button>
                <button id="generate-btn" class="w-full sm:flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-md transition duration-300 ease-in-out transform hover:scale-105 flex items-center justify-center">
                    Projeyi Oluştur
                </button>
            </div>
        </div>

        <div id="result-container" class="mt-8 bg-gray-800 border border-gray-700 rounded-lg p-6 shadow-lg min-h-[100px] hidden">
            <h3 class="text-lg font-semibold mb-2 text-gray-200">Sonuç:</h3>
            <div id="result-message" class="text-gray-300 bg-gray-900 p-4 rounded-md"></div>
        </div>
    </div>

    <script>
        const promptInput = document.getElementById('prompt-input');
        const enhanceBtn = document.getElementById('enhance-btn');
        const generateBtn = document.getElementById('generate-btn');
        const resultContainer = document.getElementById('result-container');
        const resultMessage = document.getElementById('result-message');
        
        const spinner = `<svg class="spinner h-5 w-5 mr-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;

        function showResult(message, isError = false) {
            resultContainer.classList.remove('hidden');
            resultMessage.innerHTML = `<pre class="${isError ? 'text-red-400' : 'text-green-400'}">${message}</pre>`;
        }

        // Fikir Geliştirme Butonu
        enhanceBtn.addEventListener('click', function() {
            const promptText = promptInput.value;
            if (!promptText.trim()) {
                alert('Lütfen geliştirmek için bir fikir girin.');
                return;
            }

            this.disabled = true;
            this.innerHTML = `${spinner} Geliştiriliyor...`;

            fetch('enhance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt: promptText })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message) });
                }
                return response.json();
            })
            .then(data => {
                promptInput.value = data.enhanced_prompt;
                promptInput.style.height = promptInput.scrollHeight + 'px'; // Textarea boyutunu ayarla
            })
            .catch(error => {
                showResult(`Fikir geliştirilirken hata oluştu: ${error.message}`, true);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = 'Fikrimi Geliştir ✨';
            });
        });

        // Proje Oluşturma Butonu
        generateBtn.addEventListener('click', function() {
            const promptText = promptInput.value;
            if (!promptText.trim()) {
                alert('Lütfen bir proje tanımı girin.');
                return;
            }

            this.disabled = true;
            this.innerHTML = `${spinner} Oluşturuluyor...`;
            showResult('İstek API\'ye gönderildi, lütfen bekleyin. Bu işlem proje karmaşıklığına göre birkaç dakika sürebilir...');

            fetch('generate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt: promptText })
            })
            .then(response => {
                // Hata yönetimini iyileştirme: Sunucudan gelen JSON hata mesajını yakala
                if (!response.ok) {
                    // response.json() da bir Promise döndürdüğü için zincire ekliyoruz
                    return response.json().then(errorData => {
                        // Sunucudan gelen hata mesajını veya genel bir hata mesajını fırlat
                        throw new Error(errorData.message || `HTTP hatası! Durum: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                showResult(data.message, !data.success);
            })
            .catch(error => {
                showResult(`Bir hata oluştu: ${error.message}`, true);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = 'Projeyi Oluştur';
            });
        });
    </script>

</body>
</html>
