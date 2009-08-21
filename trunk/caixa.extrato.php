<?php
/*
*	
*	Importador para Extrato da CAIXA ECONOMICA FEDERAL
*	Gera arquivo TXT no padrao aceito pelo MoeneyLog do Aurelio
*
* 	MoneyLog: http://aurelio.net/moneylog/
*
#	----------------------------------------------------------------
*	Criado por: Thiago Serra Ferreira de Carvalho
*	Email:		thiago.sfcarvalho (at) gmail.com
*	Site:		http://thiagosfcarvalho.blogspot.com/
#	----------------------------------------------------------------
#
#	Este programa é um software livre; você pode redistribui-lo e/ou
#   modifica-lo dentro dos termos da Licença Pública Geral GNU como 
#   publicada pela Fundação do Software Livre (FSF); na versão 2 da 
#   Licença, ou (na sua opnião) qualquer versão.
*
*
*	
*/
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('default_charset', 'ISO-8859-1');   
ini_set('mbstring.internal_encoding', 'ISO-8859-1');
ini_set('iconv.internal_encoding', 'ISO-8859-1');
setlocale( LC_ALL, "pt_BR", "ptb" );

$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime; 


function fullLower($str){
   // convert to entities
   $subject = htmlentities($str,ENT_QUOTES);
   $pattern = '/&([a-z])(uml|acute|circ';
   $pattern.= '|tilde|ring|elig|grave|slash|horn|cedil|th);/e';
   $replace = "'&'.strtolower('\\1').'\\2'.';'";
   $result = preg_replace($pattern, $replace, $subject);
   // convert from entities back to characters
   $htmltable = get_html_translation_table(HTML_ENTITIES);
   foreach($htmltable as $key => $value) {
      $result = ereg_replace(addslashes($value),$key,$result);
   }
   return(strtolower($result));
}

$instrucoes = "\n\n---------------------------------------------------------------\n";
$instrucoes .= " *	*	*	*	*	*	*	*\n";
$instrucoes .= "IMPORTADOR DE EXTRATO DA CAIXA ECONOMICA FEDERAL PARA MONEYLOG\n";
$instrucoes .= " *	*	*	*	*	*	*	*\n";
$instrucoes .= "---------------------------------------------------------------\n\n";
$instrucoes .= "---------------------------------------------------------------\n";
$instrucoes .= " > Desenvolvido por Thiago Serra Ferreira de Carvalho\n";
$instrucoes .= " > thiago.sfcarvalho (at) gmail.com\n";
$instrucoes .= "---------------------------------------------------------------\n\n";
if( fullLower(trim($argv[1])) != "importar" ) 
{
	$instrucoes .= "---------------------------------------------------------------\n";
	$instrucoes .= " Este programa le e importa o arquivo de extrato gerado pela\n";
	$instrucoes .= " CAIXA ECONOMICA FEDERAL, no formato OFC criando ao final um\n";
	$instrucoes .= " arquivo texto no padrao lido pelo moneylog.\n";
	$instrucoes .= " A partir dai, e so copiar os dados ao final do seu arquivo\n";
	$instrucoes .= " texto de dados do moneylog.\n";
	$instrucoes .= "---------------------------------------------------------------\n";
	$instrucoes .= " Passos:\n";
	$instrucoes .= " 1) importe no internet banking da CAIXA o seu extrato, para \n";
	$instrucoes .= " o periodo desejado;\n";
	$instrucoes .= " 2) Salve o arquivo OFC - NAO e o OFX - com o nome 'extrato.ofc'\n";
	$instrucoes .= " na pasta onde se encontra este programa;\n";
	$instrucoes .= " 3) Feito isto, basta rodar novamente este programa, fornecendo\n";
	$instrucoes .= " o parametro 'importar'. \n\n";
	$instrucoes .= "   no linux:\n";
	$instrucoes .= "   $ php caixa.extrato.php importar\n\n";
	$instrucoes .= "   ou, no windows, algo como:\n";
	$instrucoes .= "   	C:\MeusDados> <caminho da pasta de instalacao do php>\php.exe caixa.extrato.php importar\n\n";
	$instrucoes .= " Usuarios do Windows, Atencao! No exemplo acima, o arquivo 'extrato.ofc'\n";
	$instrucoes .= " deve estar na pasta 'MeusDados', juntamente com este arquivo php.\n\n";
	$instrucoes .= " 4) um arquivo chamado extrato.txt sera gerado na pasta.\n";
	$instrucoes .= " 5) copie e cole os dados no seu arquivo txt do moneylog.\n";
	$instrucoes .= " 6) é isso!\n\n";
	$instrucoes .= " Informação:\n";
	$instrucoes .= " Informação importante! o arquivo extrato.txt sempre sera apago!\n";
	$instrucoes .= " A cada importacao ele e limpo e preenchido com os dados atuais!\n";
	$instrucoes .= "---------------------------------------------------------------\n";
	echo $instrucoes;

	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$endtime = $mtime;
	$totaltime = ($endtime - $starttime); 
	echo "\nTempo de execução: " . ( $totaltime ) . "\n\n";	
	exit;
}
echo $instrucoes;
echo "->> Abrindo arquivo...\n";

//tentando abrir extrato.ofc
if(! file_exists("extrato.ofc")) {
	exit(" ERRO: Arquivo 'extrato.ofc' NAO existe na pasta do aplicativo!\n");
}
$f = fopen("extrato.ofc",'r');

$lnNovas = 0;

//criando/apagando arquivo de extrato.
$f_saida = @fopen("extrato.txt",'w+');
fwrite($f_saida, "# Troque estes dados pelos seus, separados por TAB\n");
fwrite($f_saida, "# Repito: separados por TAB, e não espaços em branco\n");
fwrite($f_saida, "# A data é no formato ANO-MES-DIA\n");

//criando classificador
$cat = array (
		"DEB CPMF" => "Juros - CPMF",
		"PG LUZ/GAS" => "Luz",
		"PAG HABIT" => "Casa - Financiamento",
		"DEB.JUROS" => "Juros - Deb.Juros",
		"DEB.IOF" => "Juros - IOF",
		"PASSAGENS" => "Diárias CAIXA",
		"DIARIAS" => "Diárias CAIXA",
		"SALARIO" => "Salário"
	);

while(!feof($f)) {
    
	$linha = fgets($f,4096);

	//grava linha do saldo atual
    if(substr($linha, 3, 6) == "STMTRS") {
		echo "->> Lendo arquivo...\n";
    	echo "->> Atualizando saldo atual...\n\n";
    	$linha = fgets($f);
    	$datas = trim(substr($linha, 12, 4).'-'.substr($linha, 16, 2).'-'.substr($linha, 18, 2));
    	$dtSaldo = trim(substr($linha, 18, 2).'/'.substr($linha, 16, 2).'/'.substr($linha, 12, 4));
    	$linha = fgets($f);
    	$linha = fgets($f);
    	$valor = trim(substr($linha, 11, 10));
    	$nrdoc = "SA999";
    	$hist = "SALDO DIA $dtSaldo";        
        $strExtrato = "$datas\t$valor\t Saldo Atual|$hist\n";
		fwrite($f_saida, $strExtrato);
        print_r($strExtrato);
		$lnNovas++;
    }

	//grava outras linhas do extrato
    if(substr($linha, 4, 7) == "STMTTRN") {

        $linha = fgets($f);
        $linha = fgets($f);
        $datas = trim(substr($linha, 14, 4).'-'.substr($linha, 18, 2).'-'.substr($linha, 20, 2));

        $linha = fgets($f);
        $valor = trim(substr($linha, 12, 10));

        $linha = fgets($f);
        $nrdoc = trim(substr($linha, 11, 6));

        $linha = fgets($f);
        $linha = fgets($f);
        $hist = trim(substr($linha, 10, 10));
		$categoria = "NAO CADASTRADO";
		if ( array_key_exists($hist, $cat) ) {
			$categoria = $cat[$hist];
		}
        $strExtrato = "$datas\t$valor\t $categoria|$hist\n";
        print_r($strExtrato);
		fwrite($f_saida, $strExtrato);
		$lnNovas++;
    }

}
echo "---------------------------------------------------------------\n";
echo "->> Linhas Importadas: $lnNovas\n";
echo "---------------------------------------------------------------\n";
echo "->> Fechando arquivo...\n";
fclose ($f);
fclose ($f_saida);
echo "---------------------------------------------------------------\n";
echo "->> Pronto!\n";
echo "---------------------------------------------------------------\n";

$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime); 

echo "\nTempo de execução: " . ( $totaltime ) . "\n\n";	
?>