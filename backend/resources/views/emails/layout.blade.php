<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Moov Universe' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Lexend', 'Gill Sans MT', 'Gill Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f5f5f5;
        }
        
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        
        .email-header {
            background: linear-gradient(135deg, #FF6B00 0%, #E55A00 100%);
            padding: 40px 20px;
            text-align: center;
        }
        
        .email-header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }
        
        .email-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin-top: 8px;
        }
        
        .email-content {
            padding: 40px 30px;
        }
        
        .email-greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
        }
        
        .email-body {
            color: #4a5568;
            font-size: 15px;
            line-height: 1.7;
        }
        
        .email-body p {
            margin-bottom: 16px;
        }
        
        .info-box {
            background: linear-gradient(135deg, #fff5eb 0%, #ffe8d1 100%);
            border-left: 4px solid #FF6B00;
            padding: 20px;
            margin: 24px 0;
            border-radius: 8px;
        }
        
        .info-box-title {
            font-weight: 700;
            color: #E55A00;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .info-label {
            font-weight: 600;
            color: #2d3748;
            min-width: 140px;
        }
        
        .info-value {
            color: #4a5568;
            flex: 1;
        }
        
        .btn {
            display: inline-block;
            padding: 16px 32px;
            background: linear-gradient(135deg, #FF6B00 0%, #E55A00 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            margin: 24px 0;
            box-shadow: 0 4px 12px rgba(255, 107, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            box-shadow: 0 6px 16px rgba(255, 107, 0, 0.4);
            transform: translateY(-2px);
        }
        
        .email-footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .email-footer p {
            color: #718096;
            font-size: 13px;
            margin-bottom: 8px;
        }
        
        .email-footer a {
            color: #FF6B00;
            text-decoration: none;
            font-weight: 600;
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
            margin: 24px 0;
        }
        
        @media only screen and (max-width: 600px) {
            .email-content {
                padding: 30px 20px;
            }
            
            .email-header h1 {
                font-size: 24px;
            }
            
            .btn {
                display: block;
                text-align: center;
            }
            
            .info-item {
                flex-direction: column;
            }
            
            .info-label {
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- Header -->
        <div class="email-header">
            <h1>🌍 Moov Universe</h1>
            <p>Plateforme de gestion des points de vente</p>
        </div>
        
        <!-- Content -->
        <div class="email-content">
            @yield('content')
        </div>
        
        <!-- Footer -->
        <div class="email-footer">
            <p><strong>Moov Africa Togo</strong></p>
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>Pour toute question, contactez-nous : <a href="mailto:contact@universe.moov-africa.tg">contact@universe.moov-africa.tg</a></p>
            <p style="margin-top: 20px; font-size: 12px; color: #a0aec0;">
                © {{ date('Y') }} Moov Africa. Tous droits réservés.
            </p>
        </div>
    </div>
</body>
</html>
