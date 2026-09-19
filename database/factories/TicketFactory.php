<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketTarget;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_number' => 'INP-'.fake()->unique()->numerify('######'),
            'requester_name' => fake()->name(),
            'whatsapp_number' => '0812'.fake()->numerify('########'),
            'description' => fake()->sentence(),
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'target' => fake()->randomElement(TicketTarget::cases()),
            'status' => TicketStatus::Pending,
        ];
    }
}
