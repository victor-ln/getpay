<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class MenuComposer
{
    public function compose(View $view)
    {
        $jsonPath = resource_path('menu/verticalMenu.json');
        if (!file_exists($jsonPath)) {
            abort(500, 'Arquivo verticalMenu.json não encontrado.');
        }
        $menuData = json_decode(file_get_contents($jsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            abort(500, 'Erro ao decodificar verticalMenu.json: ' . json_last_error_msg());
        }

        $userLevel = Auth::check() ? Auth::user()->level : null; // Obtém o nível do usuário logado

        $filteredMenu = [];
        if (isset($menuData['menu'])) {
            foreach ($menuData['menu'] as $item) {
                if (isset($item['access']) && is_array($item['access']) && $userLevel !== null && in_array($userLevel, $item['access'])) {
                    // Verifica o acesso para o item principal
                    if (isset($item['submenu']) && is_array($item['submenu'])) {
                        // Filtra o submenu também
                        $item['submenu'] = array_filter($item['submenu'], function ($subItem) use ($userLevel) {
                            return isset($subItem['access']) && is_array($subItem['access']) && in_array($userLevel, $subItem['access']);
                        });
                    }
                    $filteredMenu[] = $item;
                } elseif (!isset($item['access'])) {
                    // Itens sem restrição de acesso são sempre exibidos
                    if (isset($item['submenu']) && is_array($item['submenu'])) {
                        $item['submenu'] = array_filter($item['submenu'], function ($subItem) use ($userLevel) {
                            return isset($subItem['access']) && is_array($subItem['access']) && in_array($userLevel, $subItem['access']);
                        });
                    }
                    $filteredMenu[] = $item;
                }
            }

            $view->with('menuData', ['menu' => $filteredMenu]);
        } else {
            $view->with('menuData', $menuData); // Caso 'menu' não exista no JSON
        }
    }
}
