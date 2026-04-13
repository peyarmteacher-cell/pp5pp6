<?php
/**
 * P6 Report - Page 6: Teacher and Parent Comments
 */
?>
<div class="page behavior-comments-page <?= $student !== end($students_to_print) ? 'page-break' : '' ?>">
    <div class="p6-container">
        <!-- Teacher Comments Section -->
        <h3 class="text-center font-bold mb-4" style="font-size: 18px;">ความคิดเห็นและข้อเสนอแนะของครูประจำชั้น</h3>
        
        <table class="p6-table mb-4">
            <?php
            $categories = [
                'ด้านหน้าที่รับผิดชอบ ความเอาใจใส่การเรียน',
                'ด้านการใช้เวลาว่าง',
                'ด้านความสัมพันธ์กับ บุคคลรอบข้าง',
                'ด้านอุปนิสัย บุคลิกภาพ',
                'ด้านสุขภาพ'
            ];
            
            // Map database categories to display categories
            $db_to_display = [
                'หน้าที่รับผิดชอบ ความเอาใจใส่การเรียน' => 'ด้านหน้าที่รับผิดชอบ ความเอาใจใส่การเรียน',
                'การใช้เวลาว่าง' => 'ด้านการใช้เวลาว่าง',
                'ความสัมพันธ์กับบุคคลรอบข้าง' => 'ด้านความสัมพันธ์กับ บุคคลรอบข้าง',
                'อุปนิสัย บุคลิกภาพ' => 'ด้านอุปนิสัย บุคลิกภาพ',
                'สุขภาพ' => 'ด้านสุขภาพ'
            ];

            // Organize behavior data
            $behavior_map = [];
            foreach ($behavior_comments as $bc) {
                $display_name = $db_to_display[$bc['category_name']] ?? $bc['category_name'];
                $behavior_map[$display_name] = $bc['behavior_text'];
            }

            foreach ($categories as $cat):
                $text = $behavior_map[$cat] ?? '';
            ?>
            <tr>
                <td class="font-bold" style="width: 25%; padding: 12px 10px; text-align: center; line-height: 1.4;"><?= $cat ?></td>
                <td class="text-left" style="padding: 12px 15px; vertical-align: middle; min-height: 50px;">
                    <?= nl2br(htmlspecialchars($text)) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="sig-block" style="margin-top: 20px; text-align: center; margin-left: 40%;">
            ลงชื่อ........................................................................ครูประจำชั้น/ครูที่ปรึกษา
        </div>

        <!-- Parent Comments Section -->
        <h3 class="text-center font-bold mb-4" style="font-size: 18px; margin-top: 50px;">ความคิดเห็นและข้อเสนอแนะของผู้ปกครอง</h3>
        
        <table class="p6-table mb-4">
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td class="font-bold" style="width: 25%; padding: 12px 10px; text-align: center; line-height: 1.4;"><?= $cat ?></td>
                <td class="text-left" style="padding: 12px 15px; vertical-align: middle; min-height: 50px;">
                    &nbsp;
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="sig-block" style="margin-top: 20px; text-align: center; margin-left: 40%;">
            ลงชื่อ........................................................................ผู้ปกครอง<br>
            <div style="margin-top: 10px;">
                (........................................................................)
            </div>
        </div>
    </div>
</div>

<style>
    .behavior-comments-page {
        padding: 20mm;
    }
    .behavior-comments-page .p6-table td {
        border: 1px solid black;
        font-size: 14px;
    }
</style>
