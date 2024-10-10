<?php
date_default_timezone_set("Asia/Tokyo");

//変数を初期化する
$current_date = null;
$message = array();
$message_array = array();
$success_message = null;
$error_message = array();
$escaped = array();
$pdo = null;
$statment = null;
$res = null;

//データベースに接続する
try {
  $pdo = new PDO('mysql:charset=UTF8;dbname=bbs;host=localhost', 'root', 'root');
} catch (PDOException $e) {
  //DB接続エラーのときエラー内容を取得してエラーメッセージに格納する
  $error_message[] = $e->getMessage();

  // エラーメッセージを表示して終了する
  echo "データベース接続エラー: " . $e->getMessage();
  exit;
}

//送信して受け取ったデータは$_POSTの中に自動的に入る
//投稿データがあるときだけログを表示する
if (!empty($_POST["submitButton"])) {

  //表示名の入力をチェック
  if (empty($_POST["username"])) {
    $error_message[] = "お名前を入力してください。";
  } else {
    $escaped['username'] = htmlspecialchars($_POST["username"], ENT_QUOTES, "UTF-8");
  }

  //コメントの入力をチェック
  if (empty($_POST["comment"])) {
    $error_message[] = "コメントを入力してください。";
  } else {
    $escaped['comment'] = htmlspecialchars($_POST["comment"], ENT_QUOTES, "UTF-8");
  }

  //エラーメッセージが何もないときだけデータ保存できる
  if (empty($error_message)) {

    // 下のコメントアウトを外すとデータをログに出力するようになる
    // var_dump($_POST);

    // 現在の日時を取得する
    $current_date = date("Y-m-d H:i:s");

    // トランザクションを開始する
    $pdo->beginTransaction();

    try {

      // SQLを作成する
      $statment = $pdo->prepare("INSERT INTO `bbs-table` (username, comment, postDate) VALUES (:username, :comment, :current_date)");

      // 値をセットする
      $statment->bindParam(':username', $escaped["username"], PDO::PARAM_STR);
      $statment->bindParam(':comment', $escaped["comment"], PDO::PARAM_STR);
      $statment->bindParam(':current_date', $current_date, PDO::PARAM_STR);

      // SQLクエリを実行する
      $res = $statment->execute();

      // ここまでエラーなくできたらコミットする
      $res = $pdo->commit();
    } catch (Exception $e) {
      //エラーが発生したときはロールバック(処理取り消し)
      $pdo->rollBack();
    }

    if ($res) {
      $success_message = "コメントを書き込みました。";
    } else {
      $error_message[] = "書き込みに失敗しました。";
    }

    $statment = null;
  }
}


//DBからコメントデータを取得する
$sql = "SELECT id, username, comment, postDate FROM `bbs-table` ORDER BY postDate DESC";
$message_array = $pdo->query($sql);


//DB接続を閉じる
$pdo = null;
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>2チャンネル掲示板</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <h1 class="title">掲示板アプリだにょ</h1>
  <hr>
  <div class="boardWrapper">
    <!-- メッセージ送信成功時 -->
    <?php if (!empty($success_message)) : ?>
      <p class="success_message"><?php echo $success_message; ?></p>
    <?php endif; ?>

    <!-- バリデーションチェック時 -->
    <?php if (!empty($error_message)) : ?>
      <?php foreach ($error_message as $value) : ?>
        <div class="error_message">※<?php echo $value; ?></div>
      <?php endforeach; ?>
    <?php endif; ?>
    <section>
      <?php if (!empty($message_array)) : ?>
        <?php foreach ($message_array as $value) : ?>
          <article>
            <div class="wrapper">
              <div class="nameArea">
                <span>名前：</span>
                <p class="username"><?php echo $value['username'] ?></p>
                <time>：<?php echo date('Y/m/d H:i', strtotime($value['postDate'])); ?></time>
              </div>
              <p class="comment"><?php echo $value['comment']; ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
    <form method="POST" action="" class="formWrapper">
      <div>
        <input type="submit" value="書き込む" name="submitButton">
        <label for="usernameLabel">名前：</label>
        <input type="text" name="username">
      </div>
      <div>
        <textarea name="comment" class="commentTextArea"></textarea>
      </div>
    </form>
  </div>

</body>

</html>