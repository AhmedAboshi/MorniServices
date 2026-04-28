<?php
include('file/header.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الخدمة</title>
    <link rel="stylesheet" href="style.css">
</head>


<style>
    main{
        display:flex;
        flex-wrap: wrap;
    }
    .container{
        width: 90%;
        height: auto;
        margin: 20px auto;
        border-radius:8px;  
    }
    .product_img{
         width:50%;
    height:400px;
    }
    .product_img img{
        width: 350px;
        height: 400px;
        margin-left:40px;
        margin-bottom:20px;
    }
    .product_info{
        float: right;
        width: 400px;
        height: 400px;
        text-align: right;
        font-size:20px;
        margin-right:50px;
        padding:10px 10px;
        margin-top:30px;
    }
    .product_title{
        margin:10px 0;
    }
    .product_price{
        color: #e67e22;
        margin: 10px 0;
    }
    .product_description{
        font-size: 16px;
        text-align: center;
        margin:10px 0;
    }
   .add_cart{
     width: 100%;
    height: 35px;
    background-color:  #e67e22;
    margin-top: 10px;
    padding: 10px 29px;
    cursor: pointer;
}

.add_cart:hover{
    background-color: #e67e22;
}
.recently_added{
    float:right;
    width: 30%;
    margin-top:30px;
    border-radius: 8px;
    padding:10px 10px;
    box-shadow: 0 5px 10px rgba(0,0,0,1);
}
.added_img img{
    float:right;
    margin: 10px 10px;
    width: 70px;
    height:70px;
    margin-right:5px;
    border-radius:10px;
}
.comment_info{
    float: left; 
    height: auto;
     width: 50%;
    margin:20px 10px;
    box-shadow: 0 5px 10px rgba(0,0,0,1);
}
   h5{
    font-size: 20px;
    margin-top:  20px;
    color: black;
   text-align: left;
}
textarea{
    text-align: center;
    width: 600px;
    margin-top:20px;
    margin-left:50px;
    margin-bottom: 10px;
    padding:10px;
    border:1px solid #ccc;
    border-radius: 10px;
    height: 100px;
}
.add_comment{
     width: 100%;
    height: 35px;
    background-color:  #e67e22;
    margin-top: 10px;
    padding: 10px 29px;
    cursor: pointer;
}
.add_comment:hover{
    background-color: #e67e22;
}
.comments{
   text-align: center;
    width: 600px;
    margin-top:20px;
    margin-left:50px;
    margin-bottom: 10px;
    padding:10px;
    border:1px solid #ccc;
    border-radius: 10px;
    height: 600px;
}
.comment{
    color: black;
    font-size: larger;
    margin: 5px 5px;
    text-align: center;
    padding:10px;
    background-color: #fff;
    border: 1px solid #ddd;
    margin-bottom: 10px;
    border-radius: 5px;
    overflow: scroll;
    text-overflow: ellipsis;
}
.usename{
    padding: 4px 5px;
    text-align: right;
    color: blue;
    font-size:20px;
}
</style>
<body>
    <?php
    @$id =$_GET['id'];
    if(isset($_GET['id'])){
        $query ="SELECT * FROM product WHERE id='$id'";
        $result = mysqli_query($con,$query);
        $row=mysqli_fetch_assoc($result);
    }


?>
 <main>
<!------start img---->
<div class="container">
<div class="product_img">
<img src="uploads/img//<?PHP echo $row['proimg'];?>">
</div>
<!------end img---->
<!------start information---->
<div class="product_info">
    <h1 class="product_title"><?PHP echo @$row['proname'];?></h1>
    <h2 class="product_price"><?PHP echo @$row['proprice'];?>$ &nbsp; السعر</h2><br>
    <!----section---->
        <div class="product_section">
            <a href="section.php?section=<?php echo $row['prosection'];?>">
                <?PHP echo $row['prosection'];?></a>
        </div>
             <!---- end section---->

    <h4 class="product_description">تفاصيل الخدمة</h4>
    <p><?PHP echo $row['prodescrip'];?></p>
    <!----Quantity---->
        <div class="qty_input">
         <button class="gty_count_mins">-</button>
         <input type="number" id="quantity" name="" value="1" min="0" max="6">
         <button class="gty_count_add">+</button>
        </div>
        <!-----submit---->
        <form action="cart.php" method="POST">
    <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
    <input type="hidden" name="name" value="<?= $row['proname'] ?>">
    <input type="hidden" name="price" value="<?= $row['proprice'] ?>">
    <input type="hidden" name="img" value="<?= $row['proimg'] ?>">
    <button type="submit" name="add" class="add_cart">
        إضافة للسلة
    </button>
</form>
</a>
</div>
<!----- end submit---->
<!------end information---->
</div>
</div>
</main>

    <div class="recently_added">
        <h4>خدمات حديثة</h4>
        <?php
        $query = "SELECT * FROM product WHERE id!='$id' ORDER BY rand() LIMIT 5";
        $result = mysqli_query($con,$query);
        while($row = mysqli_fetch_assoc($result)){
            echo '<div class="added_img">
                    <a href="detalis.php?id='.$row['id'].'">
                        <img src="uploads/img/'.$row['proimg'].'" alt="">
                    </a>
                  </div>';
        }
        ?>
    </div>

    <div class="comment_info">
        <h5>قيم الخدمة</h5>
        <form action="" method="post">
            <textarea name="comment" placeholder="قيم من فضلك الخدمة"></textarea>
            <button class="add_comment" type="submit" name="add_comment">ارسال</button>
        </form>

        
       

        <h5>تقييمات العملاء</h5>
        
        <div class="comments">
            <?php
            $query = "SELECT * FROM comments ORDER BY id DESC LIMIT 10";
            $result = mysqli_query($con, $query);
            while($comment = mysqli_fetch_assoc($result)){
                 echo '<div class="usename">تقيم بواسطة:&nbsp;'.$comment['usename'].'</div>';
                echo '<div class="comment">'.$comment['comment'].'</div>';
            }
            // معالجة التعليقات
if(isset($_GET['add_comment'])){
    $comment = trim($_POST['comment']);
    if(!empty($comment)){
        $query = "INSERT INTO comments(comment) VALUES ('$comment')";
        mysqli_query($con, $query);
        echo '<script>alert("تم إضافة تعليقك بنجاح");</script>';
    } else {
        echo '<script>alert("الرجاء ملء الحقل");</script>';
    }
}
 

            ?>
        </div>
    </div>
</main>