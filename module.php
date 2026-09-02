<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2025
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Contracts\UserInterface;
use Fisharebest\Webtrees\DB;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Http\Exceptions\HttpAccessDeniedException;
use Fisharebest\Webtrees\Http\Exceptions\HttpNotFoundException;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleGlobalInterface;
use Fisharebest\Webtrees\Module\ModuleMenuInterface;
use Fisharebest\Webtrees\Module\ModuleMenuTrait;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\HtmlService;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


class UserNewsPluginModule extends AbstractModule implements ModuleCustomInterface, ModuleGlobalInterface, ModuleMenuInterface
{
    use ModuleCustomTrait;
    use ModuleMenuTrait;

    private const VIEW_NAMESPACE = 'user-news-plugin';
    private HtmlService $html_service;

    protected int $access_level = Auth::PRIV_PRIVATE;

    public function __construct(HtmlService $html_service)
    {
        $this->html_service = $html_service;

        View::registerNamespace(self::VIEW_NAMESPACE, __DIR__ . '/resources/views/');
    }

    public function title(): string
    {
        /* I18N: Name of a module */
        return I18N::translate('User news');
    }

    public function description(): string
    {
        /* I18N: Description of the “User news” module */
        return I18N::translate('News for signed-in users, written by administrators.');
    }

    public function customModuleAuthorName(): string
    {
        return 'Custom';
    }

    public function customModuleVersion(): string
    {
        return '1.0.0';
    }

    public function customModuleSupportUrl(): string
    {
        return '';
    }

    public function resourcesFolder(): string
    {
        return __DIR__ . '/resources/';
    }

    public function customTranslations(string $language): array
    {
        if (str_starts_with($language, 'de')) {
            return [
                'User news' => 'Nachrichten',
                'Anverwandte' => 'Anverwandte',
                'News for signed-in users, written by administrators.' => 'Nachrichten für angemeldete Benutzer, geschrieben von Administratoren.',
                'No news articles have been submitted.' => 'Es wurden keine Nachrichten erstellt.',
                'No news articles match your filters.' => 'Es wurden keine passenden Nachrichten gefunden.',
                'Edit' => 'Bearbeiten',
                'Delete' => 'Löschen',
                'Add a news article' => 'Nachricht erstellen',
                'Filter' => 'Filter',
                'All news' => 'Alle Nachrichten',
                'Unread news' => 'Ungelesene Nachrichten',
                'My news' => 'Meine Nachrichten',
                'New' => 'Neu',
                'News number %s' => 'Nachricht Nr. %s',
                'Search' => 'Suche',
                'Go to ID' => 'Gehe zu Nr.',
                'Apply filters' => 'Filter anwenden',
                'News item %s was not found.' => 'Nachricht %s wurde nicht gefunden.',
                'Title' => 'Titel',
                'Content' => 'Inhalt',
                'Timestamp' => 'Zeitstempel',
                'Use current timestamp' => 'Aktuellen Zeitstempel verwenden',
                'save' => 'Speichern',
                'cancel' => 'Abbrechen',
                'Are you sure you want to delete “%s”?' => 'Möchten Sie „%s“ wirklich löschen?',
                '%s does not exist.' => '%s existiert nicht.',
            ];
        }

        if (str_starts_with($language, 'en')) {
            return [
                'User news' => 'News',
                'Anverwandte' => 'Anverwandte',
                'News for signed-in users, written by administrators.' => 'News for signed-in users, written by administrators.',
                'No news articles have been submitted.' => 'No news articles have been submitted.',
                'No news articles match your filters.' => 'No news articles match your filters.',
                'Edit' => 'Edit',
                'Delete' => 'Delete',
                'Add a news article' => 'Add a news article',
                'Filter' => 'Filter',
                'All news' => 'All news',
                'Unread news' => 'Unread news',
                'My news' => 'My news',
                'New' => 'New',
                'News number %s' => 'News no. %s',
                'Search' => 'Search',
                'Go to ID' => 'Go to ID',
                'Apply filters' => 'Apply filters',
                'News item %s was not found.' => 'News item %s was not found.',
                'Title' => 'Title',
                'Content' => 'Content',
                'Timestamp' => 'Timestamp',
                'Use current timestamp' => 'Use current timestamp',
                'save' => 'save',
                'cancel' => 'cancel',
                'Are you sure you want to delete “%s”?' => 'Are you sure you want to delete “%s”?',
                '%s does not exist.' => '%s does not exist.',
            ];
        }

        return [];
    }

    public function defaultMenuOrder(): int
    {
        return 8;
    }

    public function getMenu(Tree $tree): Menu|null
    {
        if (!Auth::check()) {
            return null;
        }

        $news_label = $this->title();
        $unread = $this->unreadCount($tree);

        if ($unread > 0) {
            $news_label .= ' <span class="badge bg-danger ms-1">' . I18N::number($unread) . '</span>';
        }

        return new Menu($news_label, route('module', [
            'module' => $this->name(),
            'action' => 'Show',
            'tree'   => $tree->name(),
        ]), $unread > 0 ? 'menu-user-news menu-user-news-has-updates' : 'menu-user-news');
    }

    public function headContent(): string
    {
        $css  = e($this->assetUrl('css/user-news.css'));
        $icon = e($this->assetUrl('images/anverwandte-tree.png'));

        return '<link rel="stylesheet" href="' . $css . '"><style>:root{--user-news-icon-url:url("' . $icon . '");}</style>';
    }

    public function bodyContent(): string
    {
        return '';
    }

    public function getShowAction(ServerRequestInterface $request): ResponseInterface
    {
        $tree = Validator::attributes($request)->tree();

        if (!Auth::check()) {
            throw new HttpAccessDeniedException();
        }

        $filter = Validator::queryParams($request)->string('filter', 'all');
        $search = trim(Validator::queryParams($request)->string('search', ''));
        $goto   = Validator::queryParams($request)->integer('goto', 0);

        $allowed_filters = ['all', 'unread'];
        if (Auth::isAdmin()) {
            $allowed_filters[] = 'mine';
        }
        if (!in_array($filter, $allowed_filters, true)) {
            $filter = 'all';
        }

        if ($goto > 0) {
            $exists = DB::table('news')
                ->where('news_id', '=', $goto)
                ->where('gedcom_id', '=', $tree->id())
                ->exists();

            if ($exists) {
                $url = route('module', [
                    'module' => $this->name(),
                    'action' => 'Show',
                    'tree'   => $tree->name(),
                ]);

                return redirect($url . '?filter=all#news-' . $goto);
            }

            FlashMessages::addMessage(I18N::translate('News item %s was not found.', I18N::number($goto)), 'danger');
        }

        $utc = new DateTimeZone('UTC');

        $articles_query = DB::table('news')
            ->where('gedcom_id', '=', $tree->id());

        $last_read = Auth::user()->getPreference($this->lastReadPreference($tree));

        if ($filter === 'unread' && $last_read !== '') {
            $articles_query->where('updated', '>', $last_read);
        }

        if ($filter === 'mine' && Auth::isAdmin()) {
            $articles_query->where('user_id', '=', Auth::id());
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $articles_query->where(static function ($query) use ($like): void {
                $query->where('subject', 'like', $like)
                    ->orWhere('body', 'like', $like);
            });
        }

        $articles = $articles_query->orderByDesc('updated')
            ->get()
            ->map(static function (object $row) use ($last_read, $utc): object {
                $row->is_unread = $last_read === '' || $row->updated > $last_read;
                $timestamp = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row->updated, $utc);

                if ($timestamp === false) {
                    $row->updated = Registry::timestampFactory()->fromString($row->updated);
                } else {
                    $row->updated = Registry::timestampFactory()->make($timestamp->getTimestamp());
                }

                return $row;
            });

        $this->markAllRead($tree);

        return $this->viewResponse(self::VIEW_NAMESPACE . '::news/list', [
            'articles'    => $articles,
            'filter'      => $filter,
            'goto'        => $goto,
            'search'      => $search,
            'module_name' => $this->name(),
            'title'       => $this->title(),
            'tree'        => $tree,
        ]);
    }

    public function getEditAction(ServerRequestInterface $request): ResponseInterface
    {
        $tree = Validator::attributes($request)->tree();

        if (!Auth::isAdmin()) {
            throw new HttpAccessDeniedException();
        }

        $news_id = Validator::queryParams($request)->integer('news_id', 0);

        $timezone = new DateTimeZone(Auth::user()->getPreference(UserInterface::PREF_TIME_ZONE, 'UTC'));
        $utc      = new DateTimeZone('UTC');

        if ($news_id !== 0) {
            $row = DB::table('news')
                ->where('news_id', '=', $news_id)
                ->where('gedcom_id', '=', $tree->id())
                ->first();

            if ($row === null) {
                throw new HttpNotFoundException(I18N::translate('%s does not exist.', 'news_id:' . $news_id));
            }

            $body    = $row->body;
            $subject = $row->subject;
            $updated = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row->updated, $utc)
                ->setTimezone($timezone);
        } else {
            $body    = '';
            $subject = '';
            $updated = Registry::timestampFactory()->now(Auth::user());
        }

        return $this->viewResponse(self::VIEW_NAMESPACE . '::news/edit', [
            'body'        => $body,
            'module_name' => $this->name(),
            'news_id'     => $news_id,
            'subject'     => $subject,
            'title'       => $this->title(),
            'tree'        => $tree,
            'updated'     => $updated->format('Y-m-d\\TH:i:s'),
        ]);
    }

    public function postEditAction(ServerRequestInterface $request): ResponseInterface
    {
        $tree = Validator::attributes($request)->tree();

        if (!Auth::isAdmin()) {
            throw new HttpAccessDeniedException();
        }

        $news_id = Validator::queryParams($request)->integer('news_id', 0);
        $subject = Validator::parsedBody($request)->string('subject');
        $body    = Validator::parsedBody($request)->string('body');

        $subject = $this->html_service->sanitize($subject);
        $body    = $this->html_service->sanitize($body);

        $use_current_timestamp = Validator::parsedBody($request)->boolean('use-current-timestamp', false);

        if ($use_current_timestamp) {
            $updated = Registry::timestampFactory()->now();
        } else {
            $timestamp = Validator::parsedBody($request)->string('timestamp');
            $timezone  = new DateTimeZone(Auth::user()->getPreference(UserInterface::PREF_TIME_ZONE, 'UTC'));
            $utc       = new DateTimeZone('UTC');
            $updated   = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:s', $timestamp, $timezone)
                ->setTimezone($utc);
        }

        if ($news_id !== 0) {
            DB::table('news')
                ->where('news_id', '=', $news_id)
                ->where('gedcom_id', '=', $tree->id())
                ->update([
                    'body'    => $body,
                    'subject' => $subject,
                    'updated' => $updated->format('Y-m-d H:i:s'),
                ]);
        } else {
            DB::table('news')->insert([
                'body'      => $body,
                'subject'   => $subject,
                'gedcom_id' => $tree->id(),
                'user_id'   => Auth::id(),
                'updated'   => $updated->format('Y-m-d H:i:s'),
            ]);
        }

        $url = route('module', [
            'module' => $this->name(),
            'action' => 'Show',
            'tree'   => $tree->name(),
        ]);

        return redirect($url);
    }

    public function postDeleteAction(ServerRequestInterface $request): ResponseInterface
    {
        $tree    = Validator::attributes($request)->tree();
        $news_id = Validator::queryParams($request)->integer('news_id');

        if (!Auth::isAdmin()) {
            throw new HttpAccessDeniedException();
        }

        DB::table('news')
            ->where('news_id', '=', $news_id)
            ->where('gedcom_id', '=', $tree->id())
            ->delete();

        $url = route('module', [
            'module' => $this->name(),
            'action' => 'Show',
            'tree'   => $tree->name(),
        ]);

        return redirect($url);
    }

    private function markAllRead(Tree $tree): void
    {
        $latest = DB::table('news')
            ->where('gedcom_id', '=', $tree->id())
            ->max('updated');

        if ($latest === null) {
            $latest = Registry::timestampFactory()->now()->format('Y-m-d H:i:s');
        }

        Auth::user()->setPreference($this->lastReadPreference($tree), $latest);
    }

    private function unreadCount(Tree $tree): int
    {
        $last_read = Auth::user()->getPreference($this->lastReadPreference($tree));

        if ($last_read === '') {
            return (int) DB::table('news')
                ->where('gedcom_id', '=', $tree->id())
                ->count();
        }

        return (int) DB::table('news')
            ->where('gedcom_id', '=', $tree->id())
            ->where('updated', '>', $last_read)
            ->count();
    }

    private function lastReadPreference(Tree $tree): string
    {
        return 'user_news_last_read_' . $tree->id();
    }

}

return new UserNewsPluginModule(Registry::container()->get(HtmlService::class));
