<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// تمكين عرض الأخطاء لأغراض التطوير (إزالتها في الإنتاج)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// الاتصال بقاعدة البيانات (تعديل هذه المعلومات حسب إعداداتك)
$serverName = "127.0.0.1\\sql"; // استخدم \\ للهروب الصحيح
$connectionOptions = array(
    "Database" => "platform",
    "Uid" => "sa",
    "PWD" => "123123",
    "CharacterSet" => "UTF-8"
);

try {
    // الحصول على البيانات المرسلة من JavaScript
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        throw new Exception("Invalid JSON data received");
    }

    // إنشاء اتصال بقاعدة البيانات
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // بدء المعاملة
    $conn->beginTransaction();

    // 1. تحديث معلومات الاختبار الأساسية
    $stmt = $conn->prepare("UPDATE tests SET 
                          title = :title, 
                          type = :type 
                          WHERE id = :testID");
    $stmt->execute([
        ':title' => $data['testTitle'],
        ':type' => $data['testType'],
        ':testID' => $data['testID']
    ]);

    // 2. حذف الأقسام القديمة (اختياري - يمكنك تعديل هذا حسب منطق تطبيقك)
    $stmt = $conn->prepare("DELETE FROM sections WHERE test_id = :testID");
    $stmt->execute([':testID' => $data['testID']]);

    // 3. إضافة الأقسام الجديدة
    foreach ($data['sections'] as $section) {
        // إضافة القسم
        $stmt = $conn->prepare("INSERT INTO sections 
                              (test_id, title, duration, sort_order) 
                              VALUES 
                              (:testID, :title, :duration, :order)");
        $stmt->execute([
            ':testID' => $data['testID'],
            ':title' => $section['SectionTitle'],
            ':duration' => $section['duration'],
            ':order' => $section['order'] ?? 0
        ]);
        $sectionID = $conn->lastInsertId();

        // إضافة الأسئلة لكل قسم
        foreach ($section['questions'] as $question) {
            $stmt = $conn->prepare("INSERT INTO questions 
                                  (section_id, question_text, question_type, sort_order) 
                                  VALUES 
                                  (:sectionID, :text, :type, :order)");
            $stmt->execute([
                ':sectionID' => $sectionID,
                ':text' => $question['QuestionText'],
                ':type' => $question['QuestionType'],
                ':order' => $question['order'] ?? 0
            ]);
            $questionID = $conn->lastInsertId();

            // إضافة خيارات الإجابة للأسئلة من نوع multiple-choice
            if ($question['QuestionType'] === 'multiple-choice' && !empty($question['AnswerOptions'])) {
                foreach ($question['AnswerOptions'] as $index => $option) {
                    $isCorrect = ($index == $question['CorrectAnswerIndex']);
                    
                    $stmt = $conn->prepare("INSERT INTO answer_options 
                                          (question_id, option_text, is_correct, sort_order) 
                                          VALUES 
                                          (:questionID, :text, :correct, :order)");
                    $stmt->execute([
                        ':questionID' => $questionID,
                        ':text' => $option,
                        ':correct' => $isCorrect ? 1 : 0,
                        ':order' => $index
                    ]);
                }
            }
        }
    }

    // إتمام المعاملة
    $conn->commit();

    // إرسال استجابة نجاح
    echo json_encode([
        'success' => true,
        'message' => 'Test updated successfully',
        'testID' => $data['testID']
    ]);

} catch (PDOException $e) {
    // التراجع عن المعاملة في حالة خطأ
    if (isset($conn) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>