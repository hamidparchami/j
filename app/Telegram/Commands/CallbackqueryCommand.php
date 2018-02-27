<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Longman\TelegramBot\Commands\SystemCommands;
use App\ContentCategory;
use App\CustomerCategory;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
/**
 * Callback query command
 *
 * This command handles all callback queries sent via inline keyboard buttons.
 *
 * @see InlinekeyboardCommand.php
 */
class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'callbackquery';
    /**
     * @var string
     */
    protected $description = 'Reply to callback query';
    /**
     * @var string
     */
    protected $version = '1.1.1';
    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $callback_query     = $this->getCallbackQuery();
        $callback_query_id  = $callback_query->getId();
        $callback_data      = $callback_query->getData();
        $message_id         = $callback_query->getMessage()->getMessageId();
        $customer_id        = $callback_query->getMessage()->getChat()->getId();

        //data is something like category_id. we explode it by prefix so in future we can compare other types: category, gender, ... and do different process
        $callback_data = explode('_', $callback_data);
        //check callback type
        if ($callback_data[0] == "category") {
            $text   = "به چه موضوعی تو زیبایی علاقه داری؟".PHP_EOL;
            $text  .= " هر‌چند تا می خوای‌ از موارد زير انتخاب کن بعدش از طریق منو دکمه «\xE2\x9E\xA1 مشاهده مطالب مجله» رو بزن.";
            //check customer category by selected option in inline keyboard
            $category = CustomerCategory::where('customer_id', $customer_id)->where('category_id', $callback_data[1])->get()->first();
            //if does not exist in db
            if (is_null($category)) {
                //then insert that in db
                CustomerCategory::create(['customer_id' => $customer_id, 'category_id' => $callback_data[1]]);
            } else {
                //if does exist in db
                //then remove it from db
                $category->delete();
            }

            //customer categories
            $customer_categories = CustomerCategory::where('customer_id', $customer_id)->get(['category_id'])->implode('category_id', ',');
            $customer_categories = explode(',', $customer_categories);
            //default categories
            $categories = ContentCategory::where('is_active', 1)->get();

            $inline_keyboard_categories = [];
            foreach ($categories as $category) {
                array_push($inline_keyboard_categories, new InlineKeyboardButton(['text' => ((in_array($category['id'], $customer_categories)) ? '✅ ' : ' ') . $category['name'], 'callback_data' => 'category_' . $category['id']]));
            }

            $inline_keyboard = new InlineKeyboard(...$inline_keyboard_categories);

            $data = [
                'chat_id'      => $customer_id,
                'text'         => $text,
                'reply_markup' => $inline_keyboard,
                'message_id'   => $message_id,
            ];

            Request::editMessageText($data);

        } elseif ($callback_data[0] == "history") {
            if ($callback_data[1] == "clear") {
                //clear user history
                CustomerCategory::where('customer_id', $customer_id)->destroy();
                //send message
                $command = "keyboard";
                $text = "تاریخچه محتوای دریافتی پاک شد و حالا میتونی محتوایی که قبلا هم دریافت کرده بودی رو دوباره داشته باشی.";
                $data = [
                    'chat_id' => $customer_id,
                    'text'    => $text,
                ];

                Request::sendMessage($data);
                return $this->executeCommandHack($command);
            }

        } elseif ($callback_data[0] == "favoritecategories") {
            //execute favorite categories command
            $command = "favoritecategories";
            return $this->executeCommandHack($command);

        } elseif ($callback_data[0] == "start") {
            if ($callback_data[1] == "continue") {
                //send how to use bot message
                $command = "keyboard";
                return $this->executeCommandHack($command);
            }
        }
    }

    public function executeCommandHack($command)
    {
        $callback_query = $this->getCallbackQuery();
        $callback_query_id  = $callback_query->getId();

        if ($command_object = $this->getTelegram()->getCommandObject($command)) {
            // Get the message array.
            $new_message_array = json_decode($callback_query->getMessage()->toJson(), true);

            // Override from so that it looks like we're the ones who sent the message.
            $new_message_array['from'] = $new_message_array['chat'];

            $new_update = new Update(
                ['update_id' => '-1', 'message' => $new_message_array],
                $callback_query->getBotName()
            );
            $command_object->setUpdate($new_update)->preExecute();
        }

        return Request::answerCallbackQuery(['callback_query_id' => $callback_query_id]);
    }
}