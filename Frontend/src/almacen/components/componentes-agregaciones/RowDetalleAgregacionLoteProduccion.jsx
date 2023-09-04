import React, { useState } from "react";
import TableCell from "@mui/material/TableCell";
import TableRow from "@mui/material/TableRow";

export const RowDetalleAgregacionLoteProduccion = ({ detalle, _parseInt }) => {
  return (
    <TableRow>
      <TableCell>{detalle.nomProd}</TableCell>
      <TableCell>{detalle.simMed}</TableCell>
      <TableCell>{detalle.nomAlm}</TableCell>
      <TableCell>{detalle.desProdAgrMot}</TableCell>
      <TableCell>{_parseInt(detalle)}</TableCell> {/** detalle.canProdAgr */}
    </TableRow>
  );
};
