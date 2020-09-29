<?php
 
namespace App\Mailers;
 
use App\Ticket;
use App\User;
use App\TwitterHelper;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Log;
 
class AppMailer
{
    protected $mailer;
    protected $fromAddress = 'team@beastly.app';
    protected $fromName = 'Beastly Support (no-reply)';
    protected $to;
    protected $subject;
    protected $view;
    protected $data = [];
 
    /**
     * AppMailer constructor.
     * @param $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }
 
    public function sendTicketInformation($user, Ticket $ticket)
    {

        $this->to = "robndg@me.com";//$user->email;
 
        $this->subject = "Message Sent! [$ticket->title] [ID: $ticket->ticket_id]";
 
        $this->view = 'emails.ticket_info';
 
        $this->data = compact('user', 'ticket');
 
        return $this->deliver();
    }

    public function sendTicketInformationToDepartment($department, Ticket $ticket, $user)
    {
      
        if($user->getTwitterHelper()->getEmail() != NULL && $user->getTwitterHelper()->getUsername() != NULL){
            $this->fromAddress = $user->getTwitterHelper()->getEmail();
            $this->fromName = $user->getTwitterHelper()->getUsername();
        }

        $this->to = $department->email;
 
        $this->subject = "$ticket->title [ID: $ticket->ticket_id]";
 
        $this->view = 'emails.ticket_info_realtor';
 
        $this->data = compact('department', 'ticket', 'user');
 
        return $this->deliver();
    }
 
    public function sendTicketComments($ticketOwner, $user, Ticket $ticket, $comment)
    {

        /*if($user->realtor_id != NULL){
            $realtor = Realtor::where('id', $user->realtor_id)->first();
            $this->fromAddress = $realtor->email;
            $this->fromName = $realtor->f_name . ' [Beastly Support]';
        }*/
        $ticketUsername = $ticketOwner->getTwitterHelper()->getUsername();

        $this->to = $ticketOwner->getTwitterHelper()->getEmail();
 
        $this->subject = "RE: $ticket->title [ID: $ticket->ticket_id]";
 
        $this->view = 'emails.ticket_comments';
 
        $this->data = compact('ticketOwner', 'user', 'ticket', 'comment', 'ticketUsername');
 
        return $this->deliver();
    }

    public function sendTicketCommentsToDepartment($ticketOwner, $category, Ticket $ticket, $comment)
    {

        if($ticketOwner->getTwitterHelper()->getEmail() != NULL && $ticketOwner->getTwitterHelper()->getUsername() != NULL){
            $this->fromAddress = $ticketOwner->getTwitterHelper()->getEmail();
            $this->fromName = $ticketOwner->getTwitterHelper()->getUsername();
        }

        $this->to = $category->email; 
 
        $this->subject = "RE: $ticket->title [ID: $ticket->ticket_id]";
 
        $this->view = 'emails.ticket_comments_realtor';
 
        $this->data = compact('ticketOwner', 'category', 'ticket', 'comment');
 
        return $this->deliver();
    }
 
    public function sendTicketStatusNotification($ticketOwner, Ticket $ticket)
    {
        $this->to = $ticketOwner->email;
        $this->subject = "RE: $ticket->title [ID: $ticket->ticket_id]";
        $this->view = 'emails.ticket_status';
        $this->data = compact('ticketOwner', 'ticket');
 
        return $this->deliver();
    }
 
    public function deliver()
    {
        $this->mailer->send($this->view, $this->data, function($message){
 
            $message->from($this->fromAddress, $this->fromName)
                    ->to($this->to)->subject($this->subject);
 
        });
    }
}