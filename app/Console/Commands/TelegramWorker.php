<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Models\Student;

class TelegramWorker extends Command
{
    protected $signature = 'telegram:listen';
    protected $description = 'Listens for Telegram messages and links students';

    public function handle()
    {
        $this->info("🤖 Telegram Listener Started! Waiting for messages...");

        $lastUpdateId = 0;

        while (true) {
            try {
                // Ask Telegram for new messages
                $updates = Telegram::getUpdates(['offset' => $lastUpdateId + 1]);

                foreach ($updates as $update) {
                    $lastUpdateId = $update->getUpdateId();
                    
                    if (!isset($update->getMessage()->text)) continue;

                    $message = $update->getMessage();
                    $text = $message->text;
                    $chatId = $message->chat->id;
                    $username = $message->from->first_name;

                    // DEBUG: Print what we hear
                    $this->warn("📩 Received from $username: '$text'");

                    // LOGIC: Check for /start command
                    if (str_starts_with($text, '/start ')) {
                        $studentId = trim(str_replace('/start ', '', $text));
                        $this->info("🔎 Checking Student ID: $studentId");

                        $student = Student::where('student_id', $studentId)->first();

                        if ($student) {
                            $student->chat_id = $chatId;
                            $student->save();

                            $this->info("✅ Linked $username to ID: $studentId");
                            
                            Telegram::sendMessage([
                                'chat_id' => $chatId,
                                'text' => "🎉 Success! Welcome, {$student->name}.\nYou will now receive notifications when parcels arrive."
                            ]);
                        } else {
                            $this->error("❌ Student ID $studentId not found.");
                            Telegram::sendMessage([
                                'chat_id' => $chatId,
                                'text' => "⚠️ Error: Student ID not found in system."
                            ]);
                        }
                    }
                }
                
                sleep(2); // Wait 2 seconds

            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
                sleep(5);
            }
        }
    }
}