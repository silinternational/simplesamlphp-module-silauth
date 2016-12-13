#!/usr/bin/env bash

TABLES=(user previous_password)
SUFFIX="Base"

declare -A models
models["user"]="UserBase"
models["previous_password"]="PreviousPasswordBase"

for i in "${!models[@]}"; do
    CMD="/data/src/yii gii/model --tableName=$i --modelClass=${models[$i]} --generateRelations=all --enableI18N=1 --overwrite=1 --interactive=0 --ns=Sil\SilAuth\models"
    echo $CMD
    $CMD
done
