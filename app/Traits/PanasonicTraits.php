<?php
namespace App\Traits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

trait PanasonicTraits	
{	
	public function interactiveReply($option)
	{
		switch ($option) {
			case "Product Category":
				return 1;
				// Handle text message
				break;

			case "Retailer":
				return 2;
				// Handle image message
				break;

			case "Electrician":
				return 3;
				// Handle video message
				break;

			case "Talk to our experts":
				return 3;
				// Handle video message
				break;

			default:
				return 0;
				// Handle unknown message type or provide an error message
				break;
		}
		
	}
}