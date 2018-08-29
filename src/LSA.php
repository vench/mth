<?php

namespace PHPLsa;

/**
 * Class LSA
 * @package PHPLsa
 */
class LSA
{



    /**
     * @var int
     */
    private $nFeatures;

    /**
     * @var int
     */
    private $nMaxDocuments;

    /**
     * @var int
     */
    private $nMaxWords;


    /**
     * @var ITransformTextToMatrix
     */
    private $textTransformer= null;

    /**
     * @var TfidfText
     */
    private $tfidfText = null;

    /**
     * @var array
     */
    private $components = [];

    /**
     * LSA constructor.
     * @param int $nFeatures
     * @param int $nMaxDocuments
     * @param int $nMaxWords
     * @param int $typeCount
     */
    function __construct($nFeatures = 5, $nMaxDocuments = 1000, $nMaxWords = 100)
    {
        $this->nFeatures = $nFeatures;
        $this->nMaxDocuments = $nMaxDocuments;
        $this->nMaxWords = $nMaxWords;
    }

    /**
     * @param array $arDocuments
     * @return array
     */
    public function fitTransform(array $arDocuments):array {
        $M = $this->textTransform($arDocuments);
        $M = $this->getTfidfText()->fitTransform($M);

        list($U, $V, $S) = svd($M);
        //print_r($S); exit();
        //show(trans($V));
        //show($U); exit();
        $min = min($this->nFeatures, count($M), count($M[0]));
        trunc($U, count($M), $min);
       // trunc($V, $min, count($M[0]));

        $V = trans($V);
        trunc($V, count($M[0]), $min);
        $V = trans($V);

        //show(trans($V)); exit();
        $this->components = $U;
        $VT = $V;//trans($V);//

        $result = [];
        for ($i = 0; $i < count($VT); $i ++) {
            for ($j = 0; $j < count($VT[0]); $j ++) {
                $result[$i][$j] = $VT[$i][$j] * $S[$i];
            }
        }

       // show($S); exit();
//        show($result); exit();

        return $result;
    }

    /**
     * @param array $arDocuments
     */
    public function fit(array $arDocuments) {
        $this->fitTransform($arDocuments);
    }

    /**
     * @param array $arDocuments
     * @return array
     */
    public function transform(array $arDocuments):array {
        $M = $this->textTransform($arDocuments);
        //$ct = trans($this->components);
        //$M = trans($M);
        $ct = trans($this->components); //19x4
       // print_r($M); exit();

        $t = $this->getTfidfText();
        $M = $t->transform($M);


        return mult($ct,  $M);
    }

    /**
     *
     */
    public function save() {

    }

    /**
     *
     */
    public function load() {

    }

    /**
     * @return ITransformTextToMatrix
     */
    public function getTextTransformer() {
        if(is_null($this->textTransformer)) {
            $this->setTextTransformer(
                new TransformTextWordBool($this->nMaxWords) );
        }
        return $this->textTransformer;
    }


    /**
     * @param $query
     * @param array $trans
     * @return int
     */
    public function query($query, array $trans) {
        $qTrans = $this->transform([$query]);
        $index = -1;
        $weight = 0;
        $alpha = 0.0001;
        for($n = 0; $n < count($trans[0]); $n++) {
            $sum = 0.0;
            $sum1 = 0.0;
            $sum2 = 0.0;

            for($i = 0; $i < count($trans); $i++) {
                $sum += $trans[$i][$n] * $qTrans[$i][0];
                $sum1 += $trans[$i][$n] * $trans[$i][$n];
                $sum2 += $qTrans[$i][0] * $qTrans[$i][0];
            }

            $w = abs(  $sum / (sqrt($sum1  + $alpha) * sqrt($sum2  + $alpha) ));
            if($index == -1 || $w > $weight) {
                $index = $n;
                $weight = $w;
            }
        }

        return $index;
    }


    /**
     * @param ITransformTextToMatrix $textTransformer
     */
    public function setTextTransformer(ITransformTextToMatrix $textTransformer) {
        $this->textTransformer = $textTransformer;
    }


    /**
     * @return TfidfText
     */
    public function getTfidfText() {
        if(is_null($this->tfidfText)) {
            $this->tfidfText = new TfidfText();
        }
        return $this->tfidfText;
    }



    /**
     * @param array $arDocuments
     * @return array
     */
    private function textTransform(array $arDocuments):array {
        return $this->getTextTransformer()
            ->transform(array_slice($arDocuments, 0, $this->nMaxDocuments));
    }



}