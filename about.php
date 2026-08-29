
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>من نحن | مرني</title>

<style>

/* =========================================================
   GENERAL
========================================================= */

*{
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{
    margin:0;
    padding:0;
    font-family:
        "Tajawal",
        "Cairo",
        Arial,
        sans-serif;
    background:#f5f7fb;
    color:#172033;
    line-height:1.8;
}

a{
    text-decoration:none;
}


/* =========================================================
   NAVBAR
========================================================= */

.navbar{
    position:sticky;
    top:0;
    z-index:1000;

    background:#0b1f3a;

    min-height:76px;

    display:flex;
    align-items:center;
    justify-content:space-between;

    padding:10px 6%;

    box-shadow:
        0 4px 20px rgba(0,0,0,.10);

    gap:25px;
}

.logo-box{
    display:flex;
    align-items:center;
    min-width:160px;
}

.logo-box img{
    width:125px;
    max-height:55px;
    object-fit:contain;

    border-radius:8px;

    background:white;
    padding:4px;
}


/* MENU */

.menu{
    display:flex;
    align-items:center;
    justify-content:center;

    gap:8px;

    flex:1;
}

.menu a{
    color:#fff;

    padding:10px 16px;

    border-radius:8px;

    font-size:15px;

    transition:.3s;
}

.menu a:hover,
.menu a.active{
    background:rgba(255,255,255,.12);
    color:#fff;
}


/* CONTACT */

.nav-contact{
    color:#fff;

    font-size:13px;

    white-space:nowrap;

    text-align:center;

    line-height:1.7;
}

.nav-contact span{
    opacity:.85;
}


/* =========================================================
   HERO
========================================================= */

.hero{
    position:relative;

    min-height:430px;

    display:flex;
    align-items:center;
    justify-content:center;

    text-align:center;

    color:#fff;

    padding:80px 20px;

    overflow:hidden;

    background:
        radial-gradient(
            circle at 15% 20%,
            rgba(255,255,255,.10),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #071a33 0%,
            #0b1f3a 50%,
            #174777 100%
        );
}

.hero::before{
    content:"";

    position:absolute;

    width:420px;
    height:420px;

    border-radius:50%;

    background:rgba(255,255,255,.04);

    top:-180px;
    left:-120px;
}

.hero::after{
    content:"";

    position:absolute;

    width:350px;
    height:350px;

    border-radius:50%;

    background:rgba(255,255,255,.04);

    bottom:-180px;
    right:-100px;
}

.hero-content{
    position:relative;
    z-index:2;

    max-width:850px;
}

.hero-badge{
    display:inline-block;

    background:rgba(255,255,255,.10);

    border:1px solid rgba(255,255,255,.15);

    padding:7px 18px;

    border-radius:30px;

    font-size:14px;

    margin-bottom:20px;
}

.hero h1{
    margin:0;

    font-size:clamp(32px,5vw,55px);

    font-weight:800;

    line-height:1.3;
}

.hero p{
    margin:18px auto 0;

    max-width:700px;

    font-size:18px;

    color:rgba(255,255,255,.88);
}

.hero-buttons{
    margin-top:30px;

    display:flex;

    justify-content:center;

    gap:12px;

    flex-wrap:wrap;
}

.btn{
    display:inline-flex;

    align-items:center;
    justify-content:center;

    min-width:150px;

    padding:12px 24px;

    border-radius:10px;

    font-size:15px;

    font-weight:bold;

    transition:.3s;
}

.btn-primary{
    background:#fff;

    color:#0b1f3a;
}

.btn-primary:hover{
    transform:translateY(-3px);

    box-shadow:
        0 10px 25px rgba(0,0,0,.15);
}

.btn-outline{
    border:1px solid rgba(255,255,255,.4);

    color:#fff;
}

.btn-outline:hover{
    background:rgba(255,255,255,.10);
}


/* =========================================================
   STATS
========================================================= */

.stats-wrapper{
    max-width:1100px;

    margin:-55px auto 0;

    padding:0 20px;

    position:relative;

    z-index:5;
}

.stats{
    display:grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:18px;
}

.stat{
    background:#fff;

    padding:25px 15px;

    text-align:center;

    border-radius:16px;

    box-shadow:
        0 8px 30px rgba(20,40,70,.10);

    border:1px solid #edf0f5;

    transition:.3s;
}

.stat:hover{
    transform:translateY(-5px);

    box-shadow:
        0 14px 35px rgba(20,40,70,.14);
}

.stat-icon{
    width:48px;
    height:48px;

    margin:0 auto 10px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:12px;

    background:#eef4fb;

    font-size:22px;
}

.stat-number{
    display:block;

    color:#0b1f3a;

    font-size:28px;

    font-weight:800;

    line-height:1.3;
}

.stat-title{
    color:#687386;

    font-size:13px;
}


/* =========================================================
   MAIN CONTAINER
========================================================= */

.container{
    max-width:1100px;

    margin:60px auto;

    padding:0 20px;
}


/* =========================================================
   SECTION
========================================================= */

.section{
    background:#fff;

    border-radius:18px;

    padding:40px;

    margin-bottom:25px;

    border:1px solid #edf0f5;

    box-shadow:
        0 5px 25px rgba(20,40,70,.05);
}

.section-header{
    margin-bottom:25px;
}

.section-header small{
    display:block;

    color:#50749c;

    font-weight:bold;

    font-size:13px;

    margin-bottom:5px;
}

.section-header h2{
    margin:0;

    color:#0b1f3a;

    font-size:28px;
}

.section-header p{
    color:#697386;

    margin:8px 0 0;

    max-width:700px;
}


/* =========================================================
   STORY
========================================================= */

.story{
    display:grid;

    grid-template-columns:
        1.1fr .9fr;

    gap:35px;

    align-items:center;
}

.story-text p{
    color:#5d6778;

    font-size:16px;

    margin:0 0 15px;
}

.story-highlight{
    background:
        linear-gradient(
            135deg,
            #0b1f3a,
            #174777
        );

    color:#fff;

    border-radius:18px;

    padding:30px;

    min-height:240px;

    display:flex;

    flex-direction:column;

    justify-content:center;
}

.story-highlight h3{
    margin:0 0 12px;

    font-size:25px;
}

.story-highlight p{
    margin:0;

    color:rgba(255,255,255,.82);
}


/* =========================================================
   MISSION / VISION
========================================================= */

.mv-grid{
    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:20px;
}

.mv-card{
    padding:28px;

    border-radius:16px;

    background:#f8fafc;

    border:1px solid #edf0f5;
}

.mv-icon{
    width:52px;
    height:52px;

    display:flex;

    align-items:center;
    justify-content:center;

    border-radius:14px;

    background:#eaf1f8;

    font-size:24px;

    margin-bottom:15px;
}

.mv-card h3{
    margin:0 0 8px;

    color:#0b1f3a;

    font-size:20px;
}

.mv-card p{
    margin:0;

    color:#667085;
}


/* =========================================================
   VALUES
========================================================= */

.values{
    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:18px;
}

.value-card{
    padding:25px;

    border:1px solid #edf0f5;

    border-radius:15px;

    background:#fff;

    transition:.3s;
}

.value-card:hover{
    transform:translateY(-4px);

    border-color:#dce6f0;
}

.value-icon{
    font-size:28px;

    margin-bottom:10px;
}

.value-card h3{
    margin:0 0 6px;

    color:#0b1f3a;

    font-size:18px;
}

.value-card p{
    margin:0;

    color:#6b7280;

    font-size:14px;
}


/* =========================================================
   WHY US
========================================================= */

.why-grid{
    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:18px;
}

.why-card{
    display:flex;

    gap:15px;

    padding:22px;

    border-radius:14px;

    background:#f8fafc;

    border:1px solid #edf0f5;
}

.why-number{
    flex:none;

    width:42px;
    height:42px;

    border-radius:50%;

    background:#0b1f3a;

    color:#fff;

    display:flex;

    align-items:center;
    justify-content:center;

    font-weight:bold;
}

.why-card h3{
    margin:0 0 4px;

    color:#0b1f3a;

    font-size:17px;
}

.why-card p{
    margin:0;

    color:#697386;

    font-size:14px;
}


/* =========================================================
   TEAM
========================================================= */

.team{
    display:grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:20px;
}

.team-card{
    background:#fff;

    border:1px solid #e9edf3;

    border-radius:16px;

    overflow:hidden;

    text-align:center;

    transition:.3s;
}

.team-card:hover{
    transform:translateY(-6px);

    box-shadow:
        0 12px 30px rgba(20,40,70,.10);
}


/* =========================================================
   TEAM PHOTOS
========================================================= */

.team-photo{
    width:125px;
    height:125px;

    margin:25px auto 18px;

    border-radius:50%;

    overflow:hidden;

    background:#eef2f6;

    border:5px solid #fff;

    box-shadow:
        0 5px 18px rgba(11,31,58,.15);

    position:relative;
}

.team-photo::after{
    content:"";

    position:absolute;

    inset:0;

    border-radius:50%;

    border:1px solid rgba(11,31,58,.08);

    pointer-events:none;
}

.team-photo img{
    width:100%;
    height:100%;

    display:block;

    object-fit:cover;

    object-position:center;
}

.team-card{
    background:#fff;

    border:1px solid #e9edf3;

    border-radius:16px;

    overflow:hidden;

    text-align:center;

    transition:.3s;

    padding-bottom:5px;
}

.team-card:hover{
    transform:translateY(-6px);

    box-shadow:
        0 12px 30px rgba(20,40,70,.12);

    border-color:#dce5ef;
}

.team-card h3{
    margin:0;

    color:#172033;

    font-size:18px;

    font-weight:700;
}

.team-card p{
    margin:5px 15px 22px;

    color:#7b8494;

    font-size:13px;
}



.team-card h3{
    margin:0;

    color:#172033;

    font-size:18px;
}

.team-card p{
    margin:5px 15px 25px;

    color:#7b8494;

    font-size:13px;
}


/* =========================================================
   CTA
========================================================= */

.cta{
    background:
        linear-gradient(
            135deg,
            #0b1f3a,
            #174777
        );

    color:#fff;

    border-radius:20px;

    padding:45px 30px;

    text-align:center;

    margin-bottom:25px;
}

.cta h2{
    margin:0;

    font-size:28px;
}

.cta p{
    color:rgba(255,255,255,.82);

    margin:10px auto 25px;

    max-width:650px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:900px){

    .navbar{
        padding:12px 20px;

        flex-direction:column;
    }

    .menu{
        width:100%;

        flex-wrap:wrap;
    }

    .nav-contact{
        display:none;
    }

    .stats{
        grid-template-columns:
            repeat(2,1fr);
    }

    .story{
        grid-template-columns:1fr;
    }

    .team{
        grid-template-columns:
            repeat(2,1fr);
    }

    .values{
        grid-template-columns:
            repeat(2,1fr);
    }

}


@media(max-width:600px){

    .navbar{
        position:relative;
    }

    .logo-box{
        justify-content:center;
    }

    .menu{
        gap:2px;
    }

    .menu a{
        padding:8px 10px;

        font-size:13px;
    }

    .hero{
        min-height:390px;

        padding:60px 18px;
    }

    .hero p{
        font-size:15px;
    }

    .stats-wrapper{
        margin-top:-35px;
    }

    .stats{
        grid-template-columns:1fr 1fr;

        gap:10px;
    }

    .stat{
        padding:18px 10px;
    }

    .stat-number{
        font-size:22px;
    }

    .container{
        margin-top:40px;
    }

    .section{
        padding:25px 18px;

        border-radius:14px;
    }

    .section-header h2{
        font-size:23px;
    }

    .mv-grid,
    .why-grid,
    .values,
    .team{
        grid-template-columns:1fr;
    }

    .story-highlight{
        min-height:200px;
    }

    .cta{
        padding:35px 20px;
    }

    .cta h2{
        font-size:23px;
    }

}

</style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar">

    <div class="logo-box">

        <a href="index.php">

            <img
                src="img/logo.jpg"
                alt="مرني"
            >

        </a>

    </div>


    <div class="menu">

        <a href="index.php">
            الرئيسية
        </a>

        <a href="about.php" class="active">
            من نحن
        </a>

        <a href="contact.php">
            اتصل بنا
        </a>

    </div>


    <div class="nav-contact">

        <span>خدمة العملاء</span><br>

        +966550186105
        |
        +966920003922

    </div>

</nav>



<!-- =========================================================
     HERO
========================================================= -->

<section class="hero">

    <div class="hero-content">

        <div class="hero-badge">
            منذ عام 2015
        </div>

        <h1>
            عقدٌ من التقدّم
        </h1>

        <p>
            منصة متكاملة لخدمات السيارات والمساعدة على الطريق
            في المملكة العربية السعودية، مدعومة بالتقنية
            وخبرة تمتد لسنوات.
        </p>

        <div class="hero-buttons">

            <a
                href="contact.php"
                class="btn btn-primary"
            >
                تواصل معنا
            </a>

            <a
                href="#story"
                class="btn btn-outline"
            >
                اكتشف قصتنا
            </a>

        </div>

    </div>

</section>



<!-- =========================================================
     STATS
========================================================= -->

<div class="stats-wrapper">

    <div class="stats">

        <div class="stat">

            <div class="stat-icon">
                🚗
            </div>

            <span class="stat-number">
                +10
            </span>

            <span class="stat-title">
                سنوات من الخبرة
            </span>

        </div>


        <div class="stat">

            <div class="stat-icon">
                📍
            </div>

            <span class="stat-number">
                +50
            </span>

            <span class="stat-title">
                مدينة نخدمها
            </span>

        </div>


        <div class="stat">

            <div class="stat-icon">
                🛠️
            </div>

            <span class="stat-number">
                24/7
            </span>

            <span class="stat-title">
                خدمة ومساندة
            </span>

        </div>


        <div class="stat">

            <div class="stat-icon">
                ⭐
            </div>

            <span class="stat-number">
                +100K
            </span>

            <span class="stat-title">
                خدمة منجزة
            </span>

        </div>

    </div>

</div>



<!-- =========================================================
     MAIN
========================================================= -->

<main class="container">



<!-- =========================================================
     STORY
========================================================= -->

<section class="section" id="story">

    <div class="section-header">

        <small>
            قصتنا
        </small>

        <h2>
            من الطريق إلى مستقبل التنقل
        </h2>

    </div>


    <div class="story">

        <div class="story-text">

            <p>
                بدأت مرني عام 2015 بهدف تقديم خدمات
                المساعدة على الطريق بصورة أكثر سرعة
                وموثوقية للعملاء في المملكة العربية السعودية.
            </p>

            <p>
                ومع تطور احتياجات قطاع السيارات والتنقل،
                تطورت خدماتنا لتصبح منظومة متكاملة تساعد
                الأفراد والشركات على إدارة خدمات السيارات
                والاستجابة لاحتياجاتهم بكفاءة.
            </p>

            <p>
                نعتمد على التقنية والابتكار والخبرة التشغيلية
                لتقديم تجربة أكثر سهولة واحترافية،
                بما يتوافق مع مستهدفات التحول الرقمي
                ورؤية المملكة 2030.
            </p>

        </div>


        <div class="story-highlight">

            <h3>
                خبرة اليوم
                لمستقبل أفضل
            </h3>

            <p>
                نعمل باستمرار على تطوير خدماتنا وتقنياتنا
                لبناء تجربة تنقل أكثر أماناً وسرعة وموثوقية.
            </p>

        </div>

    </div>

</section>



<!-- =========================================================
     MISSION / VISION
========================================================= -->

<section class="section">

    <div class="section-header">

        <small>
            توجهنا
        </small>

        <h2>
            المهمة والرؤية
        </h2>

    </div>


    <div class="mv-grid">


        <div class="mv-card">

            <div class="mv-icon">
                🎯
            </div>

            <h3>
                مهمتنا
            </h3>

            <p>
                تحويل تجربة خدمات السيارات من خلال
                حلول تقنية موثوقة وسريعة وسهلة الاستخدام،
                مع التركيز على جودة الخدمة ورضا العملاء.
            </p>

        </div>


        <div class="mv-card">

            <div class="mv-icon">
                🚀
            </div>

            <h3>
                رؤيتنا
            </h3>

            <p>
                أن نكون المنصة الرائدة في الشرق الأوسط
                في مجال خدمات السيارات والتنقل بحلول عام 2030.
            </p>

        </div>


    </div>

</section>



<!-- =========================================================
     VALUES
========================================================= -->

<section class="section">

    <div class="section-header">

        <small>
            قيمنا
        </small>

        <h2>
            ما الذي نؤمن به؟
        </h2>

        <p>
            القيم التي نبني عليها خدماتنا وعلاقتنا
            مع العملاء والشركاء.
        </p>

    </div>


    <div class="values">


        <div class="value-card">

            <div class="value-icon">
                🤝
            </div>

            <h3>
                الثقة
            </h3>

            <p>
                نبني علاقات طويلة الأمد مع عملائنا
                وشركائنا من خلال الشفافية والالتزام.
            </p>

        </div>


        <div class="value-card">

            <div class="value-icon">
                ⚡
            </div>

            <h3>
                السرعة
            </h3>

            <p>
                نعمل على تقليل وقت الاستجابة وتقديم
                الخدمات بكفاءة عالية.
            </p>

        </div>


        <div class="value-card">

            <div class="value-icon">
                💡
            </div>

            <h3>
                الابتكار
            </h3>

            <p>
                نستثمر في التقنية لتطوير تجربة
                أفضل وأكثر ذكاءً.
            </p>

        </div>


        <div class="value-card">

            <div class="value-icon">
                🏆
            </div>

            <h3>
                الجودة
            </h3>

            <p>
                نضع جودة الخدمة ورضا العميل
                في مقدمة أولوياتنا.
            </p>

        </div>


        <div class="value-card">

            <div class="value-icon">
                🛡️
            </div>

            <h3>
                الموثوقية
            </h3>

            <p>
                نسعى لتقديم خدمة يمكن للعميل الاعتماد
                عليها في مختلف الظروف.
            </p>

        </div>


        <div class="value-card">

            <div class="value-icon">
                🇸🇦
            </div>

            <h3>
                طموح سعودي
            </h3>

            <p>
                نساهم في تطوير قطاع الخدمات والتنقل
                بما يتوافق مع رؤية المملكة 2030.
            </p>

        </div>


    </div>

</section>



<!-- =========================================================
     WHY US
========================================================= -->

<section class="section">

    <div class="section-header">

        <small>
            لماذا مرني؟
        </small>

        <h2>
            أكثر من مجرد خدمة طريق
        </h2>

    </div>


    <div class="why-grid">


        <div class="why-card">

            <div class="why-number">
                01
            </div>

            <div>

                <h3>
                    خبرة طويلة
                </h3>

                <p>
                    سنوات من الخبرة في تشغيل وتطوير
                    خدمات السيارات والمساعدة على الطريق.
                </p>

            </div>

        </div>


        <div class="why-card">

            <div class="why-number">
                02
            </div>

            <div>

                <h3>
                    تقنية متقدمة
                </h3>

                <p>
                    حلول رقمية تساعد على تحسين سرعة
                    الطلب والمتابعة وإدارة الخدمات.
                </p>

            </div>

        </div>


        <div class="why-card">

            <div class="why-number">
                03
            </div>

            <div>

                <h3>
                    تغطية واسعة
                </h3>

                <p>
                    شبكة خدمات مصممة لخدمة العملاء
                    في مناطق ومدن مختلفة بالمملكة.
                </p>

            </div>

        </div>


        <div class="why-card">

            <div class="why-number">
                04
            </div>

            <div>

                <h3>
                    خدمة على مدار الساعة
                </h3>

                <p>
                    جاهزون لتقديم المساعدة والدعم
                    عند الحاجة.
                </p>

            </div>

        </div>


    </div>

</section>




<!-- =========================================================
     TEAM
========================================================= -->

<section class="section">

    <div class="section-header">

        <small>
            فريق القيادة
        </small>

        <h2>
            قيادتنا
        </h2>

        <p>
            فريق من أصحاب الخبرة يعمل على قيادة
            الشركة نحو مستقبل أكثر تطوراً.
        </p>

    </div>


    <div class="team">


        <!-- سلمان السحيباني -->

        <div class="team-card">

            <div class="team-photo">

                <img
                    src="uploads/admin/أ-سلمان السحيباني.png"
                    alt="سلمان السحيباني"
                >

            </div>

            <h3>
                سلمان السحيباني
            </h3>

            <p>
                المؤسس والمدير العام
            </p>

        </div>


        <!-- سعد الدحيم -->

        <div class="team-card">

            <div class="team-photo">

                <img
                    src="uploads/admin/17833738461993.jpg"
                    alt="سعد الدحيم"
                >

            </div>

            <h3>
                سعد الدحيم
            </h3>

            <p>
                الرئيس التشغيلي
            </p>

        </div>


        <!-- خالد الوهيبي -->

        <div class="team-card">

            <div class="team-photo">

                <img
                    src="uploads/admin/أ-خالد الوهيبي.png"
                    alt="خالد الوهيبي"
                >

            </div>

            <h3>
                خالد الوهيبي
            </h3>

            <p>
                الرئيس التنفيذي للعمليات
            </p>

        </div>


        <!-- مهند النفّيعي -->

        <div class="team-card">

            <div class="team-photo">

                <img
                    src="uploads/admin/17833734937279.png"
                    alt="مهند النفّيعي"
                >

            </div>

            <h3>
                مهند النفّيعي
            </h3>

            <p>
                الرئيس التنفيذي
            </p>

        </div>


        <!-- سعود السحيباني -->

        <div class="team-card">

            <div class="team-photo">

                <img
                    src="uploads/admin/ا-سعود.jpg"
                    alt="سعود السحيباني"
                >

            </div>

            <h3>
                سعود السحيباني
            </h3>

            <p>
                رئيس تطوير الأعمال
            </p>

        </div>


    </div>

</section>





<!-- =========================================================
     CTA
========================================================= -->

<section class="cta">

    <h2>
        هل تحتاج إلى خدمات السيارات؟
    </h2>

    <p>
        تواصل معنا لمعرفة المزيد عن خدماتنا
        وحلولنا للأفراد والشركات.
    </p>

    <a
        href="contact.php"
        class="btn btn-primary"
    >
        تواصل معنا
    </a>

</section>


</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<?php

include('file/foter.php');

?>


</body>
</html>

