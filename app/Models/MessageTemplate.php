<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $subject
 * @property string $body
 * @property string|null $set_status
 * @property int $sort_order
 */
class MessageTemplate extends Model
{
    protected $fillable = ['name', 'subject', 'body', 'set_status', 'sort_order'];

    /** @return list<string> */
    public static function placeholders(): array
    {
        return ['{first_name}', '{last_name}', '{position}', '{reference}', '{island}', '{portal_link}', '{company}', '{recruiter}'];
    }

    public static function render(string $text, Application $a, ?User $sender = null): string
    {
        return strtr($text, [
            '{first_name}' => $a->first_name,
            '{last_name}' => $a->last_name,
            '{position}' => $a->roleTitle(),
            '{reference}' => $a->reference,
            '{island}' => $a->islandName(),
            '{portal_link}' => route('portal.applications.show', $a->reference),
            '{company}' => (string) candid('name'),
            '{recruiter}' => $sender->name ?? 'The Recruitment Team',
        ]);
    }
}
