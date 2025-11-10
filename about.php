<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About GPS | Gerakan Pengguna Siswa UTM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            color: #2c3e50;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .content-card {
            background: white;
            border-radius: 20px;
            padding: 0;
            width: 100%;
            max-width: 900px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-top-border {
            height: 5px;
            background: #f39c12;
            width: 100%;
        }
        
        .card-content {
            padding: 40px 50px;
        }
        
        .card-heading {
            font-size: 32px;
            font-weight: 600;
            color: #1a5490;
            margin-bottom: 25px;
            text-align: left;
        }
        
        .card-text {
            font-size: 16px;
            color: #333;
            line-height: 1.8;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .card-text:last-of-type {
            margin-bottom: 40px;
        }
        
        .feature-cards {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .feature-card {
            flex: 1;
            min-width: 250px;
            background: white;
            border-radius: 8px;
            padding: 25px;
            border-left: 4px solid;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .feature-card.blue {
            border-left-color: #2980b9;
            background: #f8fbff;
        }
        
        .feature-card.red {
            border-left-color: #e74c3c;
            background: #fff5f5;
        }
        
        .feature-card.yellow {
            border-left-color: #f39c12;
            background: #fffbf0;
        }
        
        .feature-card-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
        }
        
        .feature-card-description {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 25px 20px;
            margin-top: auto;
        }
        
        .footer-text {
            font-size: 14px;
        }
        
        .chat-widget {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: #2980b9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 1000;
            border: 3px solid white;
        }
        
        .chat-widget::after {
            content: '';
            position: absolute;
            top: -5px;
            right: -5px;
            width: 18px;
            height: 18px;
            background: #e74c3c;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .chat-icon {
            color: white;
            font-size: 28px;
        }
        
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #2980b9;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #1c5980;
        }
        
        @media (max-width: 768px) {
            .card-content {
                padding: 30px 25px;
            }
            
            .card-heading {
                font-size: 24px;
            }
            
            .feature-cards {
                flex-direction: column;
            }
            
            .feature-card {
                min-width: 100%;
            }
            
            .chat-widget {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
            }
            
            .chat-icon {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-link">← Back to Home</a>
    
    <div class="main-content">
        <div class="content-card">
            <div class="card-top-border"></div>
            <div class="card-content">
                <h1 class="card-heading">What is GPS?</h1>
                <p class="card-text">
                    Gerakan Pengguna Siswa (GPS), or Student Consumer Movement, is a school-based program that aims to educate students to become smart, ethical, and responsible consumers.
                </p>
                <p class="card-text">
                    Through this movement, students learn about consumer rights and responsibilities, how to make wise spending decisions, and the importance of avoiding waste and fraud.
                </p>
                
                <div class="feature-cards">
                    <div class="feature-card blue">
                        <h3 class="feature-card-title">Consumer Rights</h3>
                        <p class="feature-card-description">Learn about your rights as a consumer and how to protect them</p>
                    </div>
                    
                    <div class="feature-card red">
                        <h3 class="feature-card-title">Wise Spending</h3>
                        <p class="feature-card-description">Make informed decisions about your purchases and finances</p>
                    </div>
                    
                    <div class="feature-card yellow">
                        <h3 class="feature-card-title">Avoid Waste</h3>
                        <p class="feature-card-description">Understanding the importance of sustainability and avoiding fraud</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p class="footer-text">© 2025 Gerakan Pengguna Siswa UTM. All rights reserved.</p>
    </footer>
    
    <div class="chat-widget">
        <span class="chat-icon">💬</span>
    </div>
</body>
</html>

