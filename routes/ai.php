<?php

use App\Http\Middleware\RequireChatbotAccess;
use App\Mcp\Servers\LaboratorioServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

// Session authentication + CSRF. No anonymous endpoint or database credentials.
Route::middleware(['web', RequireChatbotAccess::class, 'throttle:chatbot'])->group(function () {
    Mcp::web('/mcp/laboratorio', LaboratorioServer::class);
});
