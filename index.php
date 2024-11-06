<?php
$token = "7580360314:AAFUxKLXhPXZ2pFFal5YcYk0qm7LkgTGj9A";
$admin_id = 7467968175;
$channel_id = "@TESTTSJAD";
$channel_id1 = "TESTTSJAD";
$website = "https://api.telegram.org/bot" . $token;

$update = json_decode(file_get_contents('php://input'), true);
$message = $update['message'];
$chat_id = $message['chat']['id'];
$user_first_name = $message['from']['first_name'];
$text = $message['text'];
$callback_query = $update['callback_query'];
$data = $callback_query['data'];
$callback_chat_id = $callback_query['message']['chat']['id'];
$callback_message_id = $callback_query['message']['message_id'];
$user_id = $callback_query['from']['id'];
$user_username = $message['from']['username']; // جلب يوزر الشخص

function sendMessage($chat_id, $text, $reply_markup = null) {
    global $website;
    $url = $website . "/sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($text) . "&parse_mode=HTML";
    if ($reply_markup) {
        $url .= "&reply_markup=" . json_encode($reply_markup);
    }
    file_get_contents($url);
}

if ($text == "/start") {
    $welcome_text = "👋🏻| هلا بيك $user_first_name، شلونك؟\nتگدر تشارك بالمزاد عن طريق الضغط على 'نشر معرف' جوه وتدخل للمجموعة. منتظرين مشاركاتك ومساهماتك!";
    $keyboard = [
        'inline_keyboard' => [
            [['text' => '📢 نشر معرف', 'callback_data' => 'publish_id']],
            [['text' => '📄 شروط النشر', 'callback_data' => 'publish_rules']]
        ]
    ];
    sendMessage($chat_id, $welcome_text, $keyboard);
}

if ($data == "publish_rules") {
    $rules_text = "📋 شروط النشر:\n\n- لازم يكون السعر +30 عبر آسيا او زين كاش (20 USDT)\n- لازم المعرف يكون على قناة فارغة بدون معرف تواصل، مثال: المزاد هنا @x9xxx\n- رتب اعلانك مثل ترتيب القناة\n- اذا كان المعرف مالك NFT أو ملكية وضح بالرسالة.\n\nللانضمام لمجموعة المزاد، أرسل صورة من محفظتك توضح الرصيد واسم حسابك.\n\n📢 Channel: @vvivv\n👤 Owner: @kkkkkk";
    sendMessage($callback_chat_id, $rules_text);
}

if ($data == "publish_id") {
    $choose_type_text = "اختار نوع المعرف مالتك:";
    $type_keyboard = [
        'inline_keyboard' => [
            [['text' => '🔑 ملكية', 'callback_data' => 'ownership']],
            [['text' => '💎 NFT', 'callback_data' => 'nft']]
        ]
    ];
    sendMessage($callback_chat_id, $choose_type_text, $type_keyboard);
}

if ($data == "ownership" || $data == "nft") {
    $type = $data == "ownership" ? "ملكية" : "NFT";
    $prompt_text = "• زين، هسه ارسل المعرف الخاص بيك ويه @";
    sendMessage($callback_chat_id, $prompt_text);
    file_put_contents("type_$callback_chat_id.txt", $type);
}

if ($text && file_exists("type_$chat_id.txt")) {
    $user_type = file_get_contents("type_$chat_id.txt");
    $confirmation_text = "حبي $user_first_name..\n\n- تم ارسال الإعلان مالتك، انتظر موافقة الأدمنية من فضلك ⏳.";
    sendMessage($chat_id, $confirmation_text);

    $admin_text = "طلب جديد لنشر معرف:\n\n👤 المعرف: $text\n🔖 النوع: $user_type\n📱 يوزر الشخص: @$user_username\n\nتريد تنشره؟";
    $admin_keyboard = [
        'inline_keyboard' => [
            [['text' => '✅ نشر', 'callback_data' => "approve_$chat_id"], ['text' => '❌ رفض', 'callback_data' => "reject_$chat_id"]],
            [['text' => '✉️ رد على المستخدم', 'callback_data' => "reply_$chat_id"]]
        ]
    ];
    sendMessage($admin_id, $admin_text, $admin_keyboard);
    file_put_contents("username_$chat_id.txt", $text);
    unlink("type_$chat_id.txt");
}

if (strpos($data, "approve_") === 0 || strpos($data, "reject_") === 0) {
    $target_chat_id = str_replace(["approve_", "reject_"], "", $data);
    $is_approve = strpos($data, "approve_") === 0;

    if ($is_approve) {
        $user_username = file_get_contents("username_$target_chat_id.txt");
        $user_type_text = file_exists("type_$target_chat_id.txt") && file_get_contents("type_$target_chat_id.txt") == "NFT" ? "NFT" : "ملكية";
        $published_text = "📢 Tele Username '$user_type_text': $user_username\n\n- 🚫 ممنوع تحچي داخل المناقشة\n- 🚫 ممنوع تنطي سعر أقل من السعر المطلوب\n- 📌 حدد السعر ويه العملة\n- 🚫 مخالفة القوانين تؤدي للتقييد.";
        $response = file_get_contents($website . "/sendMessage?chat_id=$channel_id&text=" . urlencode($published_text) . "&parse_mode=HTML");
        $message_data = json_decode($response, true);
        
        $message_id = $message_data['result']['message_id'];
        $channel_message_link = "https://t.me/$channel_id1/$message_id";

        $user_text = "✅ تم نشر الإعلان مالك، هاي الرابط: $channel_message_link";
        sendMessage($target_chat_id, $user_text);
    } else {
        $reject_text = "❌ عذراً، تم رفض طلبك للنشر.";
        sendMessage($target_chat_id, $reject_text);
    }
}

if (strpos($data, "reply_") === 0) {
    $target_chat_id = str_replace("reply_", "", $data);
    sendMessage($admin_id, "من فضلك اكتب الرد الذي تريد إرساله للمستخدم.");
    file_put_contents("reply_$admin_id.txt", $target_chat_id);
}

if ($text && file_exists("reply_$admin_id.txt")) {
    $target_chat_id = file_get_contents("reply_$admin_id.txt");
    sendMessage($target_chat_id, "📩 رد الأدمن: $text");
    sendMessage($admin_id, "✅ تم إرسال الرد للمستخدم.");
    unlink("reply_$admin_id.txt");
}

?>