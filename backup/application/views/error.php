<?php
	if($this->session->flashdata('errors') || $this->session->flashdata('success'))
	{
?>
<div class="well" style="text-align: center;font-size: 18px;">
	<?php 
		echo "<font color=red>".$this->session->flashdata('errors')."</font>";
		echo "<font color=green>".$this->session->flashdata('success')."</font>";
	?>
</div>
<?php
	}
	else 
	{
?>
		<div class="well" style="display: none">
		
		</div>
<?php 
	}
?>