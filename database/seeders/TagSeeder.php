<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Теги/темы для стихов. SEO-поля (meta_title, meta_description, h1, h1_description)
     * можно заполнить вручную в админке или позже через DeepSeek.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'Стихи про весну', 'slug' => 'stihi-pro-vesnu', 'sort_order' => 10],
            ['name' => 'Стихи про лето', 'slug' => 'stihi-pro-leto', 'sort_order' => 20],
            ['name' => 'Стихи про осень', 'slug' => 'stihi-pro-osen', 'sort_order' => 30],
            ['name' => 'Стихи про зиму', 'slug' => 'stihi-pro-zimu', 'sort_order' => 40],
            ['name' => 'Стихи про маму', 'slug' => 'stihi-pro-mamu', 'sort_order' => 50],
            ['name' => 'Стихи про папу', 'slug' => 'stihi-pro-papu', 'sort_order' => 60],
            ['name' => 'Стихи про любовь', 'slug' => 'stihi-pro-lyubov', 'sort_order' => 70],
            ['name' => 'Стихи про природу', 'slug' => 'stihi-pro-prirodu', 'sort_order' => 80],
            ['name' => 'Стихи про войну', 'slug' => 'stihi-pro-voynu', 'sort_order' => 90],
            ['name' => 'Стихи на 8 марта', 'slug' => 'stihi-na-8-marta', 'sort_order' => 100],
            ['name' => 'Стихи на День рождения', 'slug' => 'stihi-na-den-rozhdeniya', 'sort_order' => 110],
            ['name' => 'Стихи на Новый год', 'slug' => 'stihi-na-novyy-god', 'sort_order' => 120],
            ['name' => 'Стихи на 9 мая', 'slug' => 'stihi-na-9-maya', 'sort_order' => 130],
            ['name' => 'Стихи для детей', 'slug' => 'stihi-dlya-detey', 'sort_order' => 140],
            ['name' => 'Короткие стихи', 'slug' => 'korotkie-stihi', 'sort_order' => 150],
            ['name' => 'Длинные стихи', 'slug' => 'dlinnye-stihi', 'sort_order' => 160],
            ['name' => 'Прикольные стихи', 'slug' => 'prikolnye-stihi', 'sort_order' => 170],
            ['name' => 'Грустные стихи', 'slug' => 'grustnye-stihi', 'sort_order' => 180],
            ['name' => 'Стихи о Родине', 'slug' => 'stihi-o-rodine', 'sort_order' => 190],
            ['name' => 'Стихи о дружбе', 'slug' => 'stihi-o-druzhbe', 'sort_order' => 200],
            ['name' => 'Стихи о школе', 'slug' => 'stihi-o-shkole', 'sort_order' => 210],
            ['name' => 'Стихи о животных', 'slug' => 'stihi-o-zhivotnyh', 'sort_order' => 220],
            ['name' => 'Стихи на 23 февраля', 'slug' => 'stihi-na-23-fevralya', 'sort_order' => 230],
            ['name' => 'Стихи на 1 сентября', 'slug' => 'stihi-na-1-sentyabrya', 'sort_order' => 240],
            ['name' => 'Стихи о море', 'slug' => 'stihi-o-more', 'sort_order' => 250],
            ['name' => 'Стихи о семье', 'slug' => 'stihi-o-semye', 'sort_order' => 260],
            ['name' => 'Стихи о детстве', 'slug' => 'stihi-o-detstve', 'sort_order' => 270],
            ['name' => 'Стихи о бабушке', 'slug' => 'stihi-o-babushke', 'sort_order' => 280],
            ['name' => 'Стихи о дедушке', 'slug' => 'stihi-o-dedushke', 'sort_order' => 290],
            ['name' => 'Весёлые стихи', 'slug' => 'veselye-stihi', 'sort_order' => 300],
            ['name' => 'Романтические стихи', 'slug' => 'romanticheskie-stihi', 'sort_order' => 310],
            ['name' => 'Лирические стихи', 'slug' => 'liricheskie-stihi', 'sort_order' => 320],
            ['name' => 'Философские стихи', 'slug' => 'filosofskie-stihi', 'sort_order' => 330],
            ['name' => 'Патриотические стихи', 'slug' => 'patrioticheskie-stihi', 'sort_order' => 340],
            ['name' => 'Басни', 'slug' => 'basni', 'sort_order' => 350],
            ['name' => 'Стихи-поздравления', 'slug' => 'stihi-pozdravleniya', 'sort_order' => 360],
            ['name' => 'Четверостишия', 'slug' => 'chetverostishiya', 'sort_order' => 370],
            ['name' => 'Стихи о времени', 'slug' => 'stihi-o-vremeni', 'sort_order' => 380],
            ['name' => 'Стихи о жизни', 'slug' => 'stihi-o-zhizni', 'sort_order' => 390],
            ['name' => 'Стихи о смерти', 'slug' => 'stihi-o-smerti', 'sort_order' => 400],
            ['name' => 'Стихи о городе', 'slug' => 'stihi-o-gorode', 'sort_order' => 410],
            ['name' => 'Стихи о деревне', 'slug' => 'stihi-o-derevne', 'sort_order' => 420],
            ['name' => 'Стихи о Родине и России', 'slug' => 'stihi-o-rodine-i-rossii', 'sort_order' => 430],
            ['name' => 'Стихи про осень для детей', 'slug' => 'stihi-pro-osen-dlya-detey', 'sort_order' => 440],
            ['name' => 'Стихи про зиму для детей', 'slug' => 'stihi-pro-zimu-dlya-detey', 'sort_order' => 450],
            ['name' => 'Сказки в стихах', 'slug' => 'skazki-v-stihah', 'sort_order' => 460],
            ['name' => 'Классические стихи', 'slug' => 'klassicheskie-stihi', 'sort_order' => 470],
            ['name' => 'Современные стихи', 'slug' => 'sovremennye-stihi', 'sort_order' => 480],
        ];

        foreach ($tags as $data) {
            Tag::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'meta_title' => null,
                    'meta_description' => null,
                    'h1' => null,
                    'h1_description' => null,
                ])
            );
        }
    }
}
