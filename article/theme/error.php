
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>主题不存在</title>
<style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{
        min-height:100vh;
        display:flex;align-items:center;justify-content:center;
        font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Hiragino Sans GB","Microsoft YaHei",sans-serif;
        background:linear-gradient(135deg,#eef2f7 0%,#e6ecf5 100%);
        padding:24px;
    }
    .card{
        background:#fff;
        border-radius:16px;
        box-shadow:0 12px 40px rgba(60,80,120,.12);
        padding:56px 48px 44px;
        max-width:480px;width:100%;
        text-align:center;
        animation:fadeIn .5s ease;
    }
    .icon{
        width:88px;height:88px;margin:0 auto 24px;
        border-radius:50%;
        background:linear-gradient(135deg,#f5e9e9 0%,#fbeaea 100%);
        display:flex;align-items:center;justify-content:center;
        animation:float 2.6s ease-in-out infinite;
    }
    .icon svg{width:44px;height:44px}
    h1{font-size:22px;font-weight:600;color:#333;margin-bottom:10px}
    p{font-size:14px;line-height:1.8;color:#888;margin-bottom:30px}
    a.btn{
        display:inline-block;
        padding:11px 34px;
        border-radius:999px;
        background:linear-gradient(135deg,#4f7cf7 0%,#3b62e0 100%);
        color:#fff;font-size:14px;text-decoration:none;
        transition:transform .2s,box-shadow .2s;
        box-shadow:0 6px 18px rgba(59,98,224,.28);
    }
    a.btn:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(59,98,224,.36)}
    .footer{margin-top:28px;font-size:12px;color:#c0c6d0}
    @keyframes fadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
</style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#d76a6a" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3l10 18H2L12 3z"/>
                <line x1="12" y1="10" x2="12" y2="14"/>
                <circle cx="12" cy="17.2" r="0.4" fill="#d76a6a" stroke="none"/>
            </svg>
        </div>
        <h1>主题不存在</h1>
        <p>您访问的主题不存在，可能是因为主题名称错误或主题文件丢失。<br>您可以返回首页浏览其他内容。</p>
        <a class="btn" href="/">返回首页</a>
        <div class="footer">LyLme_Spage</div>
    </div>
</body>
</html>