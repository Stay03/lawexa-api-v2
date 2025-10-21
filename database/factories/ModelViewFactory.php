<?php

namespace Database\Factories;

use App\Models\ModelView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ModelView>
 */
class ModelViewFactory extends Factory
{
    protected $model = ModelView::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'viewable_type' => 'App\\Models\\CourtCase',
            'viewable_id' => fake()->numberBetween(1, 100),
            'user_id' => User::factory(),
            'session_id' => fake()->uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent_hash' => md5(fake()->userAgent()),
            'user_agent' => fake()->userAgent(),
            'ip_country' => fake()->country(),
            'ip_country_code' => fake()->countryCode(),
            'ip_continent' => fake()->randomElement(['Africa', 'Asia', 'Europe', 'North America', 'South America', 'Oceania', 'Antarctica']),
            'ip_continent_code' => fake()->randomElement(['AF', 'AS', 'EU', 'NA', 'SA', 'OC', 'AN']),
            'ip_region' => fake()->state(),
            'ip_city' => fake()->city(),
            'ip_timezone' => fake()->timezone(),
            'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
            'device_platform' => fake()->randomElement(['Windows', 'MacOS', 'Linux', 'iOS', 'Android']),
            'device_browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera']),
            'viewed_at' => now(),
            'is_bot' => false,
            'bot_name' => null,
            'is_search_engine' => false,
            'is_social_media' => false,
            'search_query' => null,
            'is_from_search' => false,
        ];
    }

    /**
     * Indicate that the view came from a search
     */
    public function fromSearch(?string $searchQuery = null): static
    {
        return $this->state(function (array $attributes) use ($searchQuery) {
            return [
                'search_query' => $searchQuery ?? fake()->words(3, true),
                'is_from_search' => true,
            ];
        });
    }

    /**
     * Indicate that the view did not come from a search
     */
    public function notFromSearch(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'search_query' => null,
                'is_from_search' => false,
            ];
        });
    }

    /**
     * Indicate that the view was from a bot
     */
    public function bot(?string $botName = null): static
    {
        return $this->state(function (array $attributes) use ($botName) {
            return [
                'is_bot' => true,
                'bot_name' => $botName ?? fake()->randomElement(['Googlebot', 'Bingbot', 'Facebook', 'Twitter']),
            ];
        });
    }

    /**
     * Indicate that the view was from a search engine bot
     */
    public function searchEngine(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_bot' => true,
                'is_search_engine' => true,
                'bot_name' => fake()->randomElement(['Googlebot', 'Bingbot', 'DuckDuckBot']),
            ];
        });
    }
}
