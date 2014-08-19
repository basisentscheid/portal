<?
/**
 * email notification
 *
 * @author Magnus Rosenbaum <dev@cmr.cx>
 * @package Basisentscheid
 */


class Notification {


	private $type;

	public $period;
	public $issues;
	public $issue;
	public $proposals;
	//public $proposal;

	public static $default_settings = array(
		'all'         => array('admitted'=>true, 'debate'=>true, 'voting'=>true, 'finished'=>true),
		'ngroups'     => array('admitted'=>true, 'debate'=>true, 'voting'=>true, 'finished'=>true),
		'participant' => array('admitted'=>true, 'debate'=>true, 'voting'=>true, 'finished'=>true),
		'supporter'   => array('admitted'=>true, 'debate'=>true, 'voting'=>true, 'finished'=>true),
		'proponent'   => array('admitted'=>true, 'debate'=>true, 'voting'=>true, 'finished'=>true)
	);


	/**
	 *
	 * @param string  $type
	 */
	function __construct($type) {
		$this->type = $type;
	}


	/**
	 *
	 * @return array
	 */
	public static function interests() {
		return array(
			'all'         => _("all"),
			'ngroups'     => _("ngroups"),
			'participant' => _("area participant"),
			'supporter'   => _("supporter"),
			'proponent'   => _("proponent")
		);
	}


	/**
	 *
	 * @return array
	 */
	public static function types() {
		return array(
			'admitted' => _("admitted"),
			'debate'   => _("debate"),
			'voting'   => _("voting"),
			'finished' => _("finished")
		);
	}


	/**
	 * finally send the notifications
	 */
	public function send() {

		$recipients = $this->recipients();

		// nobody to notify
		if (!$recipients) return;

		$headers = array();
		/*
		if (count($recipients) > 1) {
			$headers[] = "Bcc: ".join(", ", $recipients);
		} else {
			$to = $recipients[0];
		}
		*/
		$to = ERROR_MAIL; // for development

		list($subject, $body) = $this->content();

		send_mail($to, $subject, $body, $headers);

	}


	/**
	 * get mail adresses of the recipients
	 *
	 * @return array
	 */
	private function recipients() {

		$interest = "all";
		// TODO: other interests

		$sql = "SELECT mail FROM members
			JOIN notify ON notify.member = members.id AND notify.interest=".DB::esc($interest)."
			WHERE members.mail IS NOT NULL
				AND notify.".$this->type."=TRUE";
		return DB::fetchfieldarray($sql);

	}


	/**
	 * compose subject and body
	 *
	 * @return array
	 */
	private function content() {

		$body = "";

		switch ($this->type) {
		case "admitted":

			$ids = array();
			foreach ( $this->proposals as $proposal ) {
				$ids[] = $proposal->id;
				$body .= _("Proposal")." ".$proposal->id.": ".$proposal->title."\n";
				$body .= BASE_URL."proposal.php?id=".$proposal->id."\n";
			}

			if (count($ids) > 1) {
				$subject = sprintf(_("Proposals %s admitted"), join(", ", $ids));
				$body = _("The following proposals have been admitted").":\n\n".$body;
			} else {
				$subject = sprintf(_("Proposal %d admitted"), $ids[0]);
				$body = _("The following proposal has been admitted").":\n\n".$body;
			}

			break;
		case "debate":

			$subject = sprintf(_("Debate started in period %d"), $this->period->id);

			$body .= "Debate has started on the following proposals:\n";

			foreach ( $this->issues as $issue ) {
				$body .= "\n";
				foreach ( $issue->proposals() as $proposal ) {
					$body .= _("Proposal")." ".$proposal->id.": ".$proposal->title."\n";
					$body .= BASE_URL."proposal.php?id=".$proposal->id."\n";
				}
			}

			$body .= "\n"._("Voting preparation").": ".datetimeformat($this->period->preparation)."\n"
				._("Voting").": ".datetimeformat($this->period->voting)."\n";

			break;
		case "voting":

			$subject = sprintf(_("Voting started in period %d"), $this->period->id);

			$body .= "Voting has started on the following proposals:\n";

			foreach ( $this->issues as $issue ) {
				$body .= "\n";
				foreach ( $issue->proposals() as $proposal ) {
					$body .= _("Proposal")." ".$proposal->id.": ".$proposal->title."\n";
					$body .= BASE_URL."proposal.php?id=".$proposal->id."\n";
				}
			}

			$body .= "\n"._("Voting end").": ".datetimeformat($this->period->counting)."\n";

			break;
		case "finished":

			// TODO, voting result download interface needed

		}

		return array($subject, $body);
	}


}