<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'HireHub CRM' }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden text-gray-800">

    <aside class="w-64 bg-gray-900 text-white flex flex-col shadow-xl z-20">
        <div class="p-6 border-b border-gray-800 text-center">
            <h1 class="text-2xl font-bold text-blue-400">HireHub CRM 🚀</h1>
            <p class="text-xs text-gray-400 mt-1">الوكالة الآلية للتوظيف</p>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            
            <a href="/dashboard" class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ request()->is('dashboard') ? 'bg-blue-600 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <span class="text-xl">📊</span> 
                <span class="font-medium">لوحة القيادة</span>
            </a>

            <a href="/directory" class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ request()->is('directory') ? 'bg-blue-600 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <span class="text-xl">🗂️</span> 
                <span class="font-medium">دليل المنشآت</span>
            </a>

            <a href="/contacts" class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ request()->is('contacts') ? 'bg-blue-600 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <span class="text-xl">👥</span> 
                <span class="font-medium">جهات الاتصال</span>
            </a>
            
            <a href="/campaigns" class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ request()->is('campaigns') ? 'bg-blue-600 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <span class="text-xl">📡</span> 
                <span class="font-medium">غرفة العمليات</span>
            </a>

            <a href="/campaign-manager" class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ request()->is('campaign-manager') ? 'bg-blue-600 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <span class="text-xl">⚙️</span> 
                <span class="font-medium">هندسة الحملات</span>
            </a>

            <a href="/inbox" class="flex items-center gap-3 px-4 py-3 rounded-lg transition {{ request()->is('inbox') ? 'bg-blue-600 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <span class="text-xl">📥</span> 
                <span class="font-medium">صندوق الوارد</span>
            </a>

        </nav>

        <div class="p-4 border-t border-gray-800 bg-gray-950">
            <div class="flex items-center gap-3 px-2">
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-500 to-blue-700 flex items-center justify-center font-bold shadow-inner">
                    MZ
                </div>
                <div>
                    <p class="text-sm font-bold text-white">المدير العام</p>
                    <p class="text-xs text-green-400 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span> متصل
                    </p>
                </div>
            </div>
        </div>
    </aside>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 z-10 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-700">
                إدارة النظام
            </h2>
            <div class="flex items-center gap-4">
                <span class="text-xs font-bold text-gray-500 bg-gray-100 px-3 py-1.5 rounded-full border border-gray-200 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    الطيار الآلي نشط
                </span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 relative">
            {{ $slot }}
        </main>
        
    </div>

</body>
</html>