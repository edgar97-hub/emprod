import React from "react";
import { useState, useEffect } from "react";
import TextField from "@mui/material/TextField";
import Autocomplete from "@mui/material/Autocomplete";
import { getProveedores } from "./../../../helpers/Referenciales/proveedor/getProveedores";

export const FilterProveedor = ({ onNewInput, inputs }) => {
  const [result, setResult] = useState([]);

  const obtenerDataProveedor = async () => {
    const resultPeticion = await getProveedores();
    const formatSelect = resultPeticion.map((element) => {
      return {
        value: element.codProv,
        label: `${element.nomProv} ${element.apeProv}`,
        id: element.id,
      };
    });
    setResult(formatSelect);
  };

  useEffect(() => {
    obtenerDataProveedor();
  }, []);

  const handledChange = (event, value) => {
    onNewInput(value);
  };

  return (
    <>
      <Autocomplete
        options={result}
        disableClearable
        getOptionLabel={(option) => option.label}
        onChange={handledChange}
        value={inputs.provedor}
        onInputChange={(event, value, reason) => {
          if (reason == "input" && value == "") {
            //console.log(reason, value);
            onNewInput({ label: value });
          }
        }}
        renderInput={(params) => <TextField {...params} size="small" />}
      />
    </>
  );
};
